<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Agent;
use App\Models\ReferralCode;
use App\Models\ActivityLog;
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

        // Log user creation (using system user or null for registration)
        ActivityLog::logCreate($user, $user, $user->toArray());

        // Assign 'agent' role to user
        $user->assignRole('agent');

        // Log role assignment
        ActivityLog::logCustom($user, 'role_assigned', "Assigned 'agent' role to user {$user->email}");

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

        // Log agent creation
        ActivityLog::logCreate($user, $agent, $agent->toArray());

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

        // Log referral code creation
        ActivityLog::logCreate($user, $referralCode, $referralCode->toArray());

        // Update agent with referral code
        $agent->update(['referral_code_id' => $referralCode->id]);

        // Log agent referral code assignment
        ActivityLog::logCustom($user, 'referral_code_assigned', "Assigned referral code {$referralCode->code} to agent {$agent->id}");

        // Link user to agent
        $user->agents()->attach($agent->id);

        // Log user-agent relationship creation
        ActivityLog::logCustom($user, 'user_agent_linked', "Linked user {$user->email} to agent {$agent->id}");

        return back()->with('success', 'Agent registration completed successfully!');
    }
}
