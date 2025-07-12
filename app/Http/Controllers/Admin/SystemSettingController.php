<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SystemSettingController extends Controller
{
    /**
     * Display the system settings page.
     */
    public function index()
    {
        $settings = SystemSetting::first();

        if (!$settings) {
            // Create default settings if none exist
            $settings = SystemSetting::create([
                'commission_default_rate' => 10.00,
                'referral_code_prefix' => 'REF',
                'global_referral_usage_limit' => 100,
            ]);
        }

        return Inertia::render('Admin/SystemSettings', [
            'settings' => $settings,
        ]);
    }

    /**
     * Show the form for updating system settings.
     */
    public function edit()
    {
        $settings = SystemSetting::first();

        if (!$settings) {
            $settings = SystemSetting::create([
                'commission_default_rate' => 10.00,
                'referral_code_prefix' => 'REF',
                'global_referral_usage_limit' => 100,
            ]);
        }

        return Inertia::render('Admin/SystemSettingsUpdate', [
            'settings' => $settings,
        ]);
    }

    /**
     * Update the system settings.
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'commission_default_rate' => 'required|numeric|min:0|max:100',
            'referral_code_prefix' => 'required|string|max:10',
            'global_referral_usage_limit' => 'required|integer|min:1',
        ]);

        $settings = SystemSetting::first();

        if (!$settings) {
            $settings = new SystemSetting();
        }

        // Capture before data for activity logging
        $beforeData = $settings->exists ? $settings->toArray() : [];

        $settings->fill($request->only([
            'commission_default_rate',
            'referral_code_prefix',
            'global_referral_usage_limit',
        ]));

        $settings->save();

        // Capture after data for activity logging
        $afterData = $settings->toArray();

        // Log the system settings update activity
        ActivityLog::logUpdate($user, $settings, $beforeData, $afterData);

        return redirect()->route('admin.system-settings')
            ->with('success', 'System settings updated successfully.');
    }
}
