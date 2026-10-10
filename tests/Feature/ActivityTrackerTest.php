<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityLog;
use App\Models\ActivityUpdate;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityTrackerTest extends TestCase
{
    use RefreshDatabase;

    private int $seq = 0;

    private function makeUser(array $attrs = []): User
    {
        $this->seq++;

        return User::factory()->create($attrs + [
            'employee_id' => 'EMP' . $this->seq,
            'job_title' => 'Support Analyst',
            'phone' => '024000000' . $this->seq,
            'role' => 'member',
            'is_active' => true,
        ]);
    }

    private function makeLog(string $title = 'SMS count vs logs', $date = null): ActivityLog
    {
        $admin = User::query()->first() ?? $this->makeUser(['role' => 'admin']);
        $activity = Activity::create(['title' => $title, 'created_by' => $admin->id]);

        return ActivityLog::create(['activity_id' => $activity->id, 'log_date' => $date ?? today()]);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/reports')->assertRedirect('/login');
        $this->get('/timeline')->assertRedirect('/login');
    }

    public function test_current_sidebar_link_marks_page_active_for_accessibility(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('aria-current="page"', false)
            ->assertSee('nav--blue', false);
    }

    public function test_user_can_sign_in_and_bad_password_is_rejected(): void
    {
        $user = $this->makeUser(['email' => 'a@example.com']);

        $this->post('/login', ['email' => 'a@example.com', 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post('/login', ['email' => 'a@example.com', 'password' => 'password'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_deactivated_user_cannot_sign_in(): void
    {
        $this->makeUser(['email' => 'off@example.com', 'is_active' => false]);

        $this->post('/login', ['email' => 'off@example.com', 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_update_records_audit_row_with_bio_snapshot_and_time(): void
    {
        $user = $this->makeUser(['employee_id' => 'EMP9', 'name' => 'Kwame Mensah']);
        $log = $this->makeLog();

        $this->actingAs($user)
            ->patch(route('logs.update', $log), ['status' => 'done', 'remark' => 'Matched'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('activity_logs', ['id' => $log->id, 'status' => 'done', 'remark' => 'Matched', 'last_updated_by' => $user->id]);

        $audit = ActivityUpdate::first();
        $this->assertSame('EMP9', $audit->user_snapshot['employee_id']);
        $this->assertSame('Kwame Mensah', $audit->user_snapshot['name']);
        $this->assertNotNull($audit->created_at);
    }

    public function test_snapshot_survives_later_profile_changes(): void
    {
        $user = $this->makeUser(['job_title' => 'Analyst']);
        $log = $this->makeLog();
        app(ActivityLogService::class)->update($log, $user, 'done', 'ok');

        $user->update(['job_title' => 'Manager']);

        $this->assertSame('Analyst', ActivityUpdate::first()->user_snapshot['job_title']);
    }

    public function test_unchanged_update_does_not_create_noise(): void
    {
        $user = $this->makeUser();
        $log = $this->makeLog();
        $service = app(ActivityLogService::class);

        $this->assertTrue($service->update($log, $user, 'done', 'ok'));
        $this->assertFalse($service->update($log, $user, 'done', 'ok'));
        $this->assertSame(1, ActivityUpdate::count());
    }

    public function test_pending_requires_a_remark(): void
    {
        $this->actingAs($this->makeUser())
            ->patch(route('logs.update', $this->makeLog()), ['status' => 'pending', 'remark' => ''])
            ->assertSessionHasErrors('remark');
    }

    public function test_invalid_status_is_rejected(): void
    {
        $this->actingAs($this->makeUser())
            ->patch(route('logs.update', $this->makeLog()), ['status' => 'banana'])
            ->assertSessionHasErrors('status');
    }

    public function test_future_dated_logs_cannot_be_updated(): void
    {
        $log = $this->makeLog('Future', today()->addDay());

        $this->actingAs($this->makeUser())
            ->patch(route('logs.update', $log), ['status' => 'done'])
            ->assertSessionHasErrors('status');
        $this->assertSame(0, ActivityUpdate::count());
    }

    public function test_audit_records_are_immutable(): void
    {
        $user = $this->makeUser();
        app(ActivityLogService::class)->update($this->makeLog(), $user, 'done', 'ok');

        $this->expectException(\LogicException::class);
        ActivityUpdate::first()->update(['remark' => 'tampered']);
    }

    public function test_members_cannot_manage_activities_or_team_but_admins_can(): void
    {
        $member = $this->makeUser();
        $admin = $this->makeUser(['role' => 'admin']);

        $this->actingAs($member)->post('/activities', ['title' => 'X'])->assertForbidden();
        $this->actingAs($member)->get('/users')->assertForbidden();

        $this->actingAs($admin)->post('/activities', ['title' => 'New check'])->assertRedirect(route('activities.index'));
        $this->assertDatabaseHas('activities', ['title' => 'New check']);
    }

    public function test_pending_items_are_carried_over_to_the_next_day(): void
    {
        $user = $this->makeUser();
        $log = $this->makeLog('Backup check', today()->subDay());
        app(ActivityLogService::class)->update($log, $user, 'pending', 'Still running');

        $carried = app(ActivityLogService::class)->carriedOver(today());

        $this->assertCount(1, $carried);
        $this->assertSame('Backup check', $carried->first()->activity->title);
    }

    public function test_log_generation_is_idempotent_and_skips_inactive_activities(): void
    {
        $admin = $this->makeUser(['role' => 'admin']);
        Activity::create(['title' => 'Active', 'created_by' => $admin->id]);
        Activity::create(['title' => 'Paused', 'created_by' => $admin->id, 'is_active' => false]);

        $service = app(ActivityLogService::class);
        $service->ensureLogsForDate(today());
        $service->ensureLogsForDate(today());

        $this->assertSame(1, ActivityLog::count());
    }

    public function test_viewing_an_old_date_does_not_invent_history(): void
    {
        $admin = $this->makeUser(['role' => 'admin']);
        Activity::create(['title' => 'Active', 'created_by' => $admin->id]);

        $this->actingAs($admin)->get('/dashboard?date=' . today()->subDays(10)->toDateString())->assertOk();

        $this->assertSame(0, ActivityLog::where('log_date', today()->subDays(10)->toDateString())->count());
    }

    public function test_dashboard_and_timeline_show_who_did_what(): void
    {
        $user = $this->makeUser(['name' => 'Ama Boateng', 'employee_id' => 'EMP77']);
        $log = $this->makeLog('Check queue');
        app(ActivityLogService::class)->update($log, $user, 'done', 'Queue empty');

        $this->actingAs($user)->get('/dashboard')->assertOk()->assertSee('Check queue')->assertSee('EMP77');
        $this->actingAs($user)->get('/timeline')->assertOk()->assertSee('Ama Boateng')->assertSee('Queue empty');
        $this->actingAs($user)->get('/timeline?view=time')->assertOk()->assertSee('Ama Boateng');
    }

    public function test_report_filters_by_person_and_exports_csv(): void
    {
        $a = $this->makeUser(['name' => 'Person A']);
        $b = $this->makeUser(['name' => 'Person B']);
        $service = app(ActivityLogService::class);
        $service->update($this->makeLog('One'), $a, 'done', 'remark-alpha');
        $service->update($this->makeLog('Two'), $b, 'done', 'remark-beta');

        $this->actingAs($a)->get('/reports?user_id=' . $b->id)->assertOk()->assertSee('remark-beta')->assertDontSee('remark-alpha');

        $csv = $this->actingAs($a)->get('/reports/export')->assertOk()->streamedContent();
        $this->assertStringContainsString('Person A', $csv);
        $this->assertStringContainsString('Employee ID', $csv);
    }

    public function test_csv_export_defuses_formula_injection(): void
    {
        $user = $this->makeUser();
        app(ActivityLogService::class)->update($this->makeLog(), $user, 'done', '=HYPERLINK("http://evil")');

        $csv = $this->actingAs($user)->get('/reports/export')->streamedContent();

        $this->assertStringContainsString("'=HYPERLINK", $csv);
    }

    public function test_wake_endpoint_reports_server_and_database_ok_without_login(): void
    {
        $this->getJson('/wake')->assertOk()->assertJson(['ok' => true]);
    }

    public function test_unknown_pages_show_the_friendly_404(): void
    {
        $this->get('/this-page-does-not-exist')->assertNotFound()->assertSee('Page not found');
    }

    public function test_install_command_creates_first_admin_and_is_idempotent(): void
    {
        $args = [
            '--name' => 'Efua Asante', '--email' => 'efua@example.com', '--employee-id' => 'EMP001',
            '--job-title' => 'Support Lead', '--password' => 'a-strong-pass', '--no-interaction' => true,
        ];

        $this->artisan('tracker:install', $args)->assertExitCode(0);
        $this->artisan('tracker:install', $args)->assertExitCode(0);

        $this->assertSame(1, User::where('role', 'admin')->count());
        $admin = User::where('email', 'efua@example.com')->first();
        $this->assertTrue($admin->must_change_password); // set from a non-interactive run
        $this->assertNotSame('a-strong-pass', $admin->password);
    }

    public function test_install_command_does_nothing_without_details_when_not_interactive(): void
    {
        $this->artisan('tracker:install', ['--no-interaction' => true])->assertExitCode(0);

        $this->assertSame(0, User::count());
    }

    public function test_new_team_member_must_change_temporary_password_first(): void
    {
        $admin = $this->makeUser(['role' => 'admin']);

        $this->actingAs($admin)->post('/users', [
            'name' => 'New Person', 'email' => 'new@example.com', 'employee_id' => 'EMP500',
            'job_title' => 'Analyst', 'role' => 'member', 'password' => 'temp-pass-1',
        ])->assertRedirect(route('users.index'));

        $new = User::where('email', 'new@example.com')->first();
        $this->assertTrue($new->must_change_password);

        $this->actingAs($new)->get('/dashboard')->assertRedirect(route('profile.edit'));

        $this->actingAs($new)->put('/profile', [
            'name' => 'New Person', 'job_title' => 'Analyst',
            'current_password' => 'temp-pass-1', 'password' => 'my-own-pass-2', 'password_confirmation' => 'my-own-pass-2',
        ])->assertRedirect(route('dashboard'));

        $this->assertFalse($new->fresh()->must_change_password);
        $this->actingAs($new->fresh())->get('/dashboard')->assertOk();
    }
}
