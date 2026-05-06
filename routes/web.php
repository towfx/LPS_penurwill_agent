<?php

use App\Http\Controllers\Admin\AgentController;
use App\Http\Controllers\AgentProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect('/get-started');
});

Route::get('/get-started', function () {
    return Inertia::render('GetStarted');
})->name('get-started');

Route::get('/register-as-agent', [App\Http\Controllers\AgentRegistrationController::class, 'show'])->name('register-as-agent');
Route::post('/register-as-agent', [App\Http\Controllers\AgentRegistrationController::class, 'store'])->name('register-as-agent.store');
Route::post('/register-as-agent/save-draft', [App\Http\Controllers\AgentRegistrationController::class, 'saveDraft'])->name('register-as-agent.save-draft');
Route::post('/register-as-agent/verify-email', [App\Http\Controllers\AgentRegistrationController::class, 'verifyEmail'])->name('register-as-agent.verify-email');
Route::post('/register-as-agent/resend-code', [App\Http\Controllers\AgentRegistrationController::class, 'resendCode'])->name('register-as-agent.resend-code');
Route::post('/register-as-agent/initiate-stripe', [App\Http\Controllers\AgentRegistrationController::class, 'initiateStripe'])->name('register-as-agent.initiate-stripe');
Route::post('/register-as-agent/submit-payment', [App\Http\Controllers\AgentRegistrationController::class, 'submitManualPayment'])->name('register-as-agent.submit-payment');
Route::post('/register-as-agent/skip-payment', [App\Http\Controllers\AgentRegistrationController::class, 'skipPayment'])->name('register-as-agent.skip-payment');
Route::get('/register-as-agent/payment/success', [App\Http\Controllers\AgentRegistrationController::class, 'stripeSuccess'])->name('register-as-agent.payment.success');
Route::get('/register-as-agent/payment/cancelled', [App\Http\Controllers\AgentRegistrationController::class, 'stripeCancelled'])->name('register-as-agent.payment.cancelled');

Route::post('/get-started/check-email', [App\Http\Controllers\GetStartedController::class, 'checkEmail'])->name('get-started.check-email');

// Static Terms & Conditions page (linked from registration Step 5 T&C checkbox).
Route::get('/terms', fn () => Inertia::render('Terms'))->name('terms');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    // First-login onboarding guide (all authenticated users)
    Route::get('/get-started-guide', [App\Http\Controllers\Agent\OnboardingController::class, 'show'])->name('get-started-guide');
    Route::post('/get-started-guide/complete', [App\Http\Controllers\Agent\OnboardingController::class, 'complete'])->name('get-started-guide.complete');

    Route::get('/dashboard', function () {
        $user = auth()->user();

        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }

        // QNA-12: business_partner agents share the agent dashboard.
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
        Route::get('/agents/list', fn () => Inertia::render('Admin/AgentsList'))->name('agents.list');
        Route::get('/agents/add', [AgentController::class, 'create'])->name('agents.add');
        Route::post('/agents/store', [AgentController::class, 'store'])->name('agents.store');
        Route::get('/agents/{id}/view', [AgentController::class, 'show'])->name('agents.view');
        Route::get('/agents/{id}/update', [AgentController::class, 'edit'])->name('agents.update');
        Route::put('/agents/{id}/update', [AgentController::class, 'update'])->name('agents.update.store');
        Route::post('/agents/{id}/update', [AgentController::class, 'update'])->name('agents.update.store.post');
        Route::post('/agents/{id}/approve', [AgentController::class, 'approve'])->name('agents.approve');
        Route::get('/agents/{id}/file/{field}', [AgentController::class, 'downloadFile'])->name('agents.file.download');
        Route::get('/agents/agents.xls', [AgentController::class, 'export'])->name('agents.export');
        Route::get('/agents/parents', [AgentController::class, 'parents'])->name('agents.parents');

        // Sales — refund (commission reversal)
        Route::post('/sales/{sale}/refund', [App\Http\Controllers\Admin\SaleController::class, 'markAsRefunded'])
            ->name('sales.refund');

        // Fee payments
        Route::get('/fee-payments', [App\Http\Controllers\Admin\FeePaymentController::class, 'index'])->name('fee-payments.index');
        Route::post('/fee-payments', [App\Http\Controllers\Admin\FeePaymentController::class, 'store'])->name('fee-payments.store');

        // Commissions
        Route::get('/commissions/list', [App\Http\Controllers\Admin\CommissionController::class, 'index'])->name('commissions.list');
        Route::get('/commission/detail', [App\Http\Controllers\Admin\CommissionController::class, 'detail'])->name('commission.detail');

        // Payouts
        Route::get('/payouts', [App\Http\Controllers\Admin\PayoutController::class, 'index'])->name('payouts.list');
        Route::get('/payout/{id}', [App\Http\Controllers\Admin\PayoutController::class, 'show'])->name('payout.show');
        Route::post('/payout/{id}/upload-bank-transfer', [App\Http\Controllers\Admin\PayoutController::class, 'uploadBankTransfer'])->name('payout.upload-bank-transfer');
        Route::post('/payout/{id}/mark-as-paid', [App\Http\Controllers\Admin\PayoutController::class, 'markAsPaid'])->name('payout.mark-as-paid');
        Route::get('/payout/{id}/download-bank-transfer', [App\Http\Controllers\Admin\PayoutController::class, 'downloadBankTransfer'])->name('payout.download-bank-transfer');
        Route::get('/payout/create', [App\Http\Controllers\Admin\PayoutController::class, 'create'])->name('payout.create');
        Route::post('/payout/store', [App\Http\Controllers\Admin\PayoutController::class, 'store'])->name('payout.store');
        Route::get('/payout/{id}/update', [App\Http\Controllers\Admin\PayoutController::class, 'edit'])->name('payout.update');
        Route::put('/payout/{id}/update', [App\Http\Controllers\Admin\PayoutController::class, 'update'])->name('payout.update.store');

        // System Settings
        Route::get('/system-settings', [App\Http\Controllers\Admin\SystemSettingController::class, 'index'])->name('system-settings');
        Route::get('/system-settings/update', [App\Http\Controllers\Admin\SystemSettingController::class, 'edit'])->name('system-settings.edit');
        Route::put('/system-settings/update', [App\Http\Controllers\Admin\SystemSettingController::class, 'update'])->name('system-settings.update');

        // Partners
        Route::get('/partners/list', [App\Http\Controllers\Admin\PartnerController::class, 'index'])->name('partners.list');
        Route::get('/partners/add', [App\Http\Controllers\Admin\PartnerController::class, 'create'])->name('partners.add');
        Route::post('/partners/store', [App\Http\Controllers\Admin\PartnerController::class, 'store'])->name('partners.store');
        Route::get('/partners/{id}/view', [App\Http\Controllers\Admin\PartnerController::class, 'show'])->name('partners.view');
        Route::get('/partners/{id}/update', [App\Http\Controllers\Admin\PartnerController::class, 'edit'])->name('partners.update');
        Route::put('/partners/{id}/update', [App\Http\Controllers\Admin\PartnerController::class, 'update'])->name('partners.update.store');
        Route::delete('/partners/{id}/delete', [App\Http\Controllers\Admin\PartnerController::class, 'destroy'])->name('partners.delete');

        // Activity Log
        Route::get('/activity-log', [App\Http\Controllers\Admin\ActivityLogController::class, 'index'])->name('activity-log');
        Route::get('/activity-log/export', [App\Http\Controllers\Admin\ActivityLogController::class, 'export'])->name('activity-log.export');

        // Agent lifecycle actions
        Route::post('/agents/{id}/reject', [AgentController::class, 'reject'])->name('agents.reject');

        // Payout cancel
        Route::post('/payout/{id}/cancel', [App\Http\Controllers\Admin\PayoutController::class, 'cancel'])->name('payout.cancel');
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
        Route::get('/profile/file/{field}', [AgentProfileController::class, 'downloadFile'])->name('profile.file.download');
        Route::get('/commissions', [App\Http\Controllers\Agent\CommissionController::class, 'index'])->name('commissions');
        Route::get('/commissions/detail', [App\Http\Controllers\Agent\CommissionController::class, 'detail'])->name('commissions.detail');
        Route::get('/sales', [App\Http\Controllers\Agent\SalesController::class, 'index'])->name('sales');
        Route::get('/payouts', [App\Http\Controllers\Agent\PayoutController::class, 'index'])->name('payouts');
        Route::get('/payout/{id}', [App\Http\Controllers\Agent\PayoutController::class, 'show'])->name('payout.show');
        Route::get('/payout/{id}/download-bank-transfer', [App\Http\Controllers\Agent\PayoutController::class, 'downloadBankTransfer'])->name('payout.download-bank-transfer');
        Route::get('/request-payout', [App\Http\Controllers\Agent\RequestPayoutController::class, 'index'])->name('request-payout');
        Route::post('/request_payout', [App\Http\Controllers\Agent\RequestPayoutController::class, 'store']);
        Route::get('/payout/{id}/detail', [App\Http\Controllers\Agent\PayoutController::class, 'show'])->name('payout.detail');

        // Payment completion (post-registration)
        Route::get('/payment/complete', [App\Http\Controllers\AgentRegistrationController::class, 'completePayment'])->name('payment.complete');
        Route::post('/payment/complete', [App\Http\Controllers\AgentRegistrationController::class, 'submitPayment'])->name('payment.complete.submit');

        // Referral stats
        Route::get('/referral', [App\Http\Controllers\Agent\ReferralController::class, 'index'])->name('referral');

        // Inbox / notifications
        Route::get('/inbox', [App\Http\Controllers\Agent\NotificationController::class, 'index'])->name('inbox');
        Route::post('/inbox/{id}/read', [App\Http\Controllers\Agent\NotificationController::class, 'markRead'])->name('inbox.read');
        Route::post('/inbox/read-all', [App\Http\Controllers\Agent\NotificationController::class, 'markAllRead'])->name('inbox.read-all');

        // Suspension appeal + re-approval
        Route::post('/appeal-suspension', [App\Http\Controllers\Agent\AppealController::class, 'store'])->name('appeal-suspension');
        Route::post('/request-approval', [AgentController::class, 'requestApproval'])->name('request-approval');
    });

});
