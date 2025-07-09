<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

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
    Route::get('/admin/agents/{id}/view', fn($id) => Inertia::render('Admin/AgentView', ['id' => $id]))->name('admin.agents.view');
    Route::get('/admin/agents/{id}/update', fn($id) => Inertia::render('Admin/AgentUpdate', ['id' => $id]))->name('admin.agents.update');

    // Commissions
    Route::get('/admin/commissions/list', fn() => Inertia::render('Admin/CommissionsList'))->name('admin.commissions.list');
    Route::get('/admin/commissions/{id}/view', fn($id) => Inertia::render('Admin/CommissionView', ['id' => $id]))->name('admin.commissions.view');

    // Agent routes
    Route::get('/agent/dashboard', fn() => Inertia::render('Agent/Dashboard'))->name('agent.dashboard');
    Route::get('/agent/profile', fn() => Inertia::render('Agent/Profile'))->name('agent.profile');
    Route::get('/agent/commissions', fn() => Inertia::render('Agent/Commissions'))->name('agent.commissions');
});
