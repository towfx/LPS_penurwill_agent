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
        $user = auth()->user();

        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->hasRole('agent')) {
            return redirect()->route('agent.dashboard');
        }

        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/design/design-01', function () {
        return Inertia::render('Design/Design01');
    })->name('design.design01');

    // Profile routes (accessible to all authenticated users)
    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');

    // Admin routes (require admin role)
    Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', function () {
            return redirect()->route('admin.dashboard');
        })->name('index');

        Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

        // Agents
        Route::get('/agents/list', fn() => Inertia::render('Admin/AgentsList'))->name('agents.list');
        Route::get('/agents/add', fn() => Inertia::render('Admin/AgentsAdd'))->name('agents.add');
        Route::post('/agents/store', [AgentController::class, 'store'])->name('agents.store');
        Route::get('/agents/{id}/view', [AgentController::class, 'show'])->name('agents.view');
        Route::get('/agents/{id}/update', [AgentController::class, 'edit'])->name('agents.update');
        Route::put('/agents/{id}/update', [AgentController::class, 'update'])->name('agents.update.store');

        // Commissions
        Route::get('/commissions/list', [App\Http\Controllers\Admin\CommissionController::class, 'index'])->name('commissions.list');
        Route::get('/commission/detail', [App\Http\Controllers\Admin\CommissionController::class, 'detail'])->name('commission.detail');

        // Payouts
        Route::get('/payout/create', [App\Http\Controllers\Admin\PayoutController::class, 'create'])->name('payout.create');
        Route::post('/payout/store', [App\Http\Controllers\Admin\PayoutController::class, 'store'])->name('payout.store');
        Route::get('/payout/{id}/update', [App\Http\Controllers\Admin\PayoutController::class, 'edit'])->name('payout.update');
        Route::put('/payout/{id}/update', [App\Http\Controllers\Admin\PayoutController::class, 'update'])->name('payout.update.store');

        // System Settings
        Route::get('/system-settings', [App\Http\Controllers\Admin\SystemSettingController::class, 'index'])->name('system-settings');
        Route::get('/system-settings/update', [App\Http\Controllers\Admin\SystemSettingController::class, 'edit'])->name('system-settings.edit');
        Route::put('/system-settings/update', [App\Http\Controllers\Admin\SystemSettingController::class, 'update'])->name('system-settings.update');
    });

    // Agent routes (require agent role)
    Route::middleware(['agent'])->prefix('agent')->name('agent.')->group(function () {
        Route::get('/', function () {
            return redirect()->route('agent.dashboard');
        })->name('index');

        Route::get('/dashboard', [\App\Http\Controllers\Agent\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/profile', [AgentProfileController::class, 'show'])->name('profile');
        Route::get('/profile/edit', [AgentProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile/edit', [AgentProfileController::class, 'update'])->name('profile.update');
        Route::get('/commissions', [App\Http\Controllers\Agent\CommissionController::class, 'index'])->name('commissions');
        Route::get('/commissions/detail', [App\Http\Controllers\Agent\CommissionController::class, 'detail'])->name('commissions.detail');
        Route::get('/payout/{id}/detail', [App\Http\Controllers\Agent\PayoutController::class, 'show'])->name('payout.detail');
    });
});
