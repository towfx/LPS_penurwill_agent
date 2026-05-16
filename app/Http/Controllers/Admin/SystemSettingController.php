<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\SystemSetting;
use App\Services\CommissionConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SystemSettingController extends Controller
{
    public function __construct(protected CommissionConfig $commissionConfig) {}

    public function index()
    {
        $settings = SystemSetting::first() ?? SystemSetting::create($this->defaults());

        return Inertia::render('Admin/SystemSettings', [
            'settings' => $settings,
        ]);
    }

    public function edit()
    {
        $settings = SystemSetting::first() ?? SystemSetting::create($this->defaults());

        return Inertia::render('Admin/SystemSettingsUpdate', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $rateKeys = SystemSetting::RATE_KEYS;
        $rules = [
            'referral_code_prefix' => 'required|string|max:50',
            'skip_zero_commissions' => 'sometimes|boolean',
            'reversal_time_limit' => 'sometimes|integer|min:1|max:3650',
            'email_verification_max_retry' => 'sometimes|integer|min:1|max:100',
            'renewal_reminder_days_before' => 'sometimes|integer|min:1|max:365',
            'membership_duration_days' => 'sometimes|integer|min:1|max:36500',
            'role_name_agent' => 'sometimes|string|max:100',
            'role_name_leader' => 'sometimes|string|max:100',
            'role_name_business_partner' => 'sometimes|string|max:100',
            'commission_calc_type' => 'sometimes|in:percentage,fixed',
            'partner_commission_calc_type' => 'sometimes|in:percentage,fixed',
            'commission_fixed_amount' => 'sometimes|nullable|numeric|min:0',
            'partner_commission_fixed_amount' => 'sometimes|nullable|numeric|min:0',
        ];
        foreach ($rateKeys as $key) {
            $rules["{$key}_percentage"] = 'sometimes|numeric|min:0|max:100';
            $rules["{$key}_fixed_amount"] = 'sometimes|numeric|min:0';
            $rules["{$key}_calc_type"] = 'sometimes|in:percentage,fixed';
        }
        foreach (['business_partner', 'leader', 'agent'] as $role) {
            $rules["entry_fee_{$role}"] = 'sometimes|numeric|min:0';
            $rules["renewal_fee_{$role}"] = 'sometimes|numeric|min:0';
        }

        $validated = $request->validate($rules);

        $settings = SystemSetting::first() ?? new SystemSetting();
        $beforeData = $settings->exists ? $settings->toArray() : [];

        $settings->fill($validated);
        $settings->save();

        $this->commissionConfig->flush();

        ActivityLog::logUpdate($user, $settings, $beforeData, $settings->toArray());

        return redirect()->route('admin.system-settings')
            ->with('success', 'System settings updated successfully.');
    }

    protected function defaults(): array
    {
        return [
            'referral_code_prefix' => 'PENURWILL-',
        ];
    }
}
