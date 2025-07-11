<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Admin\AgentController;
use App\Http\Controllers\AgentProfileController;

Route::get('/', function () {
    return redirect('/get-started');
});

Route::get('/get-started', function () {
    return Inertia::render('GetStarted');
})->name('get-started');

Route::get('/register-as-agent', [App\Http\Controllers\AgentRegistrationController::class, 'show'])->name('register-as-agent');
Route::post('/register-as-agent', [App\Http\Controllers\AgentRegistrationController::class, 'store'])->name('register-as-agent.store');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/design/design-01', function () {
        return Inertia::render('Design/Design01');
    })->name('design.design01');

    Route::get('/admin/dashboard', function () {
        return Inertia::render('Admin/Dashboard', [
            'layout' => 'Design/AdminLayout',
        ]);
    })->name('admin.dashboard');

    // Agents
    Route::get('/admin/agents/list', fn() => Inertia::render('Admin/AgentsList'))->name('admin.agents.list');
    Route::get('/admin/agents/add', fn() => Inertia::render('Admin/AgentsAdd'))->name('admin.agents.add');
    Route::post('/admin/agents/store', [AgentController::class, 'store'])->name('admin.agents.store');
    Route::get('/admin/agents/{id}/view', [AgentController::class, 'show'])->name('admin.agents.view');
    Route::get('/admin/agents/{id}/update', [AgentController::class, 'edit'])->name('admin.agents.update');
    Route::put('/admin/agents/{id}/update', [AgentController::class, 'update'])->name('admin.agents.update.store');

    // Commissions
    Route::get('/admin/commissions/list', fn() => Inertia::render('Admin/CommissionsList'))->name('admin.commissions.list');
    Route::get('/admin/commissions/{id}/view', fn($id) => Inertia::render('Admin/CommissionView', ['id' => $id]))->name('admin.commissions.view');

    // Agent routes
    Route::get('/agent/dashboard', fn() => Inertia::render('Agent/Dashboard'))->name('agent.dashboard');
    Route::get('/agent/profile', [AgentProfileController::class, 'show'])->name('agent.profile');
    Route::get('/agent/profile/edit', [AgentProfileController::class, 'edit'])->name('agent.profile.edit');
    Route::put('/agent/profile/edit', [AgentProfileController::class, 'update'])->name('agent.profile.update');
    Route::get('/agent/commissions', fn() => Inertia::render('Agent/Commissions'))->name('agent.commissions');
});
