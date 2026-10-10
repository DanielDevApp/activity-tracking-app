<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TimelineController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WakeController;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\RequirePasswordChange;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

// Health/wake-up check: no session, no auth, no data.
Route::get('/wake', WakeController::class)
    ->withoutMiddleware([
        StartSession::class,
        ShareErrorsFromSession::class,
        \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class,
    ])
    ->name('wake');

// There is deliberately no public registration: admins create accounts.
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware(['auth', EnsureUserIsActive::class, RequirePasswordChange::class])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::patch('/logs/{log}', [ActivityLogController::class, 'update'])
        ->middleware('throttle:60,1')->name('logs.update');

    Route::get('/timeline', [TimelineController::class, 'index'])->name('timeline');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::middleware(EnsureAdmin::class)->group(function () {
        Route::resource('activities', ActivityController::class)->except('show');
        Route::resource('users', UserController::class)->except(['show', 'destroy']);
    });
});
