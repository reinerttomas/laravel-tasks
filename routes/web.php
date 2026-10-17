<?php

declare(strict_types=1);

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Projects\ProjectArchiveController;
use App\Http\Controllers\Projects\ProjectController;
use App\Http\Controllers\Tasks\MyTaskController;
use App\Http\Controllers\Tasks\TaskController;
use App\Http\Controllers\Teams\AcceptTeamInvitationController;
use App\Http\Controllers\Teams\DeclineTeamInvitationController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
        Route::get('my-tasks', MyTaskController::class)->name('my-tasks');

        Route::get('projects', [ProjectController::class, 'index'])->name('projects.index');
        Route::post('projects', [ProjectController::class, 'store'])->name('projects.store');

        Route::scopeBindings()->group(function () {
            Route::get('projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
            Route::patch('projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
            Route::delete('projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
            Route::post('projects/{project}/archive', [ProjectArchiveController::class, 'store'])->name('projects.archive');
            Route::delete('projects/{project}/archive', [ProjectArchiveController::class, 'destroy'])->name('projects.restore');

            Route::post('projects/{project}/tasks', [TaskController::class, 'store'])->name('tasks.store');
            Route::get('tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
            Route::patch('tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
            Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
        });
    });

Route::middleware(['auth'])->group(function () {
    Route::post('invitations/{invitation}/accept', AcceptTeamInvitationController::class)->name('invitations.accept');
    Route::delete('invitations/{invitation}', DeclineTeamInvitationController::class)->name('invitations.decline');
});

require __DIR__.'/settings.php';
