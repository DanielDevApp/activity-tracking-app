<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class InstallTracker extends Command
{
    protected $signature = 'tracker:install
        {--name= : Administrator full name}
        {--email= : Administrator email (used to sign in)}
        {--employee-id= : Administrator employee ID}
        {--job-title= : Administrator job title}
        {--password= : Administrator password (min 8 characters)}';

    protected $description = 'Create the first administrator. Safe to run on every deploy: does nothing once an admin exists.';

    public function handle(): int
    {
        if (User::where('role', User::ROLE_ADMIN)->exists()) {
            $this->info('An administrator already exists. Nothing to do.');

            return self::SUCCESS;
        }

        $config = config('tracker.admin');
        $interactive = $this->input->isInteractive();

        $data = [
            'name' => $this->option('name') ?: $config['name'],
            'email' => $this->option('email') ?: $config['email'],
            'employee_id' => $this->option('employee-id') ?: $config['employee_id'],
            'job_title' => $this->option('job-title') ?: $config['job_title'],
            'password' => $this->option('password') ?: $config['password'],
        ];

        if ($interactive) {
            $this->line('Create the first administrator account.');
            $data['name'] = $data['name'] ?: $this->ask('Full name');
            $data['email'] = $data['email'] ?: $this->ask('Work email (used to sign in)');
            $data['employee_id'] = $data['employee_id'] ?: $this->ask('Employee ID');
            $data['job_title'] = $data['job_title'] ?: $this->ask('Job title', 'Support Lead');
            $data['password'] = $data['password'] ?: $this->secret('Password (at least 8 characters)');
        }

        if (collect($data)->contains(fn ($value) => blank($value))) {
            $this->warn('No administrator created: details are missing. Set ADMIN_NAME, ADMIN_EMAIL, ADMIN_EMPLOYEE_ID and ADMIN_PASSWORD, or run this command in a terminal.');

            return self::SUCCESS;
        }

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'employee_id' => ['required', 'string', 'max:30', 'unique:users,employee_id'],
            'job_title' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        User::create($data + [
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
            // A password that was typed into an environment variable should be replaced at first sign-in.
            'must_change_password' => ! $interactive,
        ]);

        $this->info("Administrator {$data['email']} created.");

        return self::SUCCESS;
    }
}
