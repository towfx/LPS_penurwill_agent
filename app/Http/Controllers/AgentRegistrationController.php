<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Agent;
use App\Models\ReferralCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AgentRegistrationController extends Controller
{
    public function show(Request $request)
    {
        return Inertia::render('RegisterAsAgent', [
            'email' => $request->get('email', '')
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'profile_type' => 'required|in:individual,company',
            'individual_name' => 'required_if:profile_type,individual|nullable|string|max:255',
            'individual_phone' => 'required_if:profile_type,individual|nullable|string|max:255',
            'individual_address' => 'required_if:profile_type,individual|nullable|string',
            'company_representative_name' => 'required_if:profile_type,company|nullable|string|max:255',
            'company_name' => 'required_if:profile_type,company|nullable|string|max:255',
            'company_registration_number' => 'required_if:profile_type,company|nullable|string|max:255',
            'company_address' => 'required_if:profile_type,company|nullable|string',
            'company_phone' => 'required_if:profile_type,company|nullable|string|max:255',
            'password' => [
                'required',
                'string',
                'min:12',
                'confirmed',
                //'regex:#^(?=.*[0-9])(?=.*[!@#\\$%^&*()_+\-=\[\]{}|;:,.<>?])#'
            ],
            'terms' => 'required|accepted'
        ], [
            'password.regex' => 'Password must contain at least one number and one special character (!@#$%^&*()_+-=[]{}|;:,.<>?)',
            'password.min' => 'Password must be at least 12 characters long',
            'email.unique' => 'This email address is already registered. Please use a different email or try logging in.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Create user
        $user = User::create([
            'name' => $request->profile_type === 'individual'
                ? $request->individual_name
                : $request->company_representative_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'email_verified_at' => now(),
        ]);

        // Assign 'agent' role to user
        $user->assignRole('agent');

        // Create agent
        $agentData = [
            'profile_type' => $request->profile_type,
            'status' => 'active',
        ];

        if ($request->profile_type === 'individual') {
            $agentData['individual_name'] = $request->individual_name;
            $agentData['individual_phone'] = $request->individual_phone;
            $agentData['individual_address'] = $request->individual_address;
        } else {
            $agentData['company_representative_name'] = $request->company_representative_name;
            $agentData['company_name'] = $request->company_name;
            $agentData['company_registration_number'] = $request->company_registration_number;
            $agentData['company_address'] = $request->company_address;
            $agentData['company_phone'] = $request->company_phone;
        }

        $agent = Agent::create($agentData);

        // Create referral code for this agent
        $systemSetting = \App\Models\SystemSetting::first();
        $referralCode = ReferralCode::create([
            'agent_id' => $agent->id,
            'code' => $systemSetting->referral_code_prefix . strtoupper(Str::random(8)),
            'is_active' => true,
            'commission_rate' => $systemSetting->commission_default_rate,
            'usage_limit' => $systemSetting->global_referral_usage_limit,
            'used_count' => 0,
            'expires_at' => now()->addYears(5),
        ]);

        // Update agent with referral code
        $agent->update(['referral_code_id' => $referralCode->id]);

        // Link user to agent
        $user->agents()->attach($agent->id);

        return back()->with('success', 'Agent registration completed successfully!');
    }
}
