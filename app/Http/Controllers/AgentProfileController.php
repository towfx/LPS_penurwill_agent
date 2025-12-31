<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AgentProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = Auth::user();
        $agent = $user->agents()->with(['bankAccount', 'referralCode'])->first();

        return Inertia::render('Agent/Profile', [
            'agent' => $agent,
            'penurwillWebsiteUrl' => config('app.penurwill-website-url'),
        ]);
    }

    public function edit(Request $request)
    {
        $user = Auth::user();
        $agent = $user->agents()->with(['bankAccount', 'referralCode'])->first();

        return Inertia::render('Agent/ProfileEdit', [
            'agent' => $agent,
            'penurwillWebsiteUrl' => config('app.penurwill-website-url'),
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $agent = $user->agents()->first();
        if (! $agent) {
            abort(404);
        }

        // Capture before data for activity logging
        $beforeData = $agent->toArray();
        if ($agent->bankAccount) {
            $beforeData['bank_account'] = $agent->bankAccount->toArray();
        }
        if ($agent->referralCode) {
            $beforeData['referral_code'] = $agent->referralCode->toArray();
        }

        $data = $request->validate([
            'profile_type' => 'required|in:individual,company',
            'individual_name' => 'nullable|string|max:255',
            'individual_phone' => 'nullable|string|max:255',
            'individual_email' => 'nullable|email|max:255',
            'individual_address' => 'nullable|string',
            'company_representative_name' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'company_registration_number' => 'nullable|string|max:255',
            'company_address' => 'nullable|string',
            'company_phone' => 'nullable|string|max:255',
            'company_email_address' => 'nullable|email|max:255',
            'status' => 'required|in:active,inactive,suspended,banned',
            // Bank account fields
            'bank_account_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'iban' => 'nullable|string|max:255',
            'swift_code' => 'nullable|string|max:255',
            // Referral code fields
            'referral_code' => 'nullable|string|max:255|unique:referral_codes,code,'.($agent->referralCode->id ?? 'NULL').',id',
        ]);

        if ($data['profile_type'] === 'individual') {
            $data['company_representative_name'] = null;
            $data['company_name'] = null;
            $data['company_registration_number'] = null;
            $data['company_address'] = null;
            $data['company_phone'] = null;
            $data['company_email_address'] = null;
        } else {
            $data['individual_name'] = null;
            $data['individual_phone'] = null;
            $data['individual_address'] = null;
        }

        $agent->update($data);

        // Update or create bank account
        if ($agent->bankAccount) {
            $agent->bankAccount->update([
                'account_name' => $data['bank_account_name'],
                'account_number' => $data['bank_account_number'],
                'bank_name' => $data['bank_name'],
                'iban' => $data['iban'],
                'swift_code' => $data['swift_code'],
            ]);
        } else {
            if ($data['bank_account_name']) { 
                $agent->bankAccount()->create([
                    'account_name' => $data['bank_account_name'],
                    'account_number' => $data['bank_account_number'],
                    'bank_name' => $data['bank_name'],
                    'iban' => $data['iban'],
                    'swift_code' => $data['swift_code'],
                ]);
            }
        }

        // Update referral code
        if ($agent->referralCode && $data['referral_code']) {
            $agent->referralCode->update([
                'code' => $data['referral_code'],
            ]);
        } elseif ($data['referral_code'] && ! $agent->referralCode) {
            // Create new referral code if agent doesn't have one
            $systemSetting = \App\Models\SystemSetting::first();
            $referralCode = \App\Models\ReferralCode::create([
                'agent_id' => $agent->id,
                'code' => $data['referral_code'],
                'is_active' => true,
                'commission_rate' => $systemSetting->commission_default_rate,
                'used_count' => 0,
                'expires_at' => now()->addYears(5),
            ]);
            $agent->update(['referral_code_id' => $referralCode->id]);
        }

        // Refresh agent data to get updated relationships
        $agent->refresh();
        $agent->load(['bankAccount', 'referralCode']);

        // Capture after data for activity logging
        $afterData = $agent->toArray();
        if ($agent->bankAccount) {
            $afterData['bank_account'] = $agent->bankAccount->toArray();
        }
        if ($agent->referralCode) {
            $afterData['referral_code'] = $agent->referralCode->toArray();
        }

        // Log the profile update activity
        ActivityLog::logUpdate($user, $agent, $beforeData, $afterData);

        return redirect()->route('agent.profile')->with('success', 'Profile updated successfully!');
    }
}
