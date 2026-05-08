<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Agent;
use App\Models\ReferralCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AgentRegistrationController extends Controller
{
    public function show(Request $request)
    {
        $email = $request->get('email', '');
        $invalidEmail = false;

        if (! empty($email)) {
            $validator = Validator::make(['email' => $email], ['email' => 'required|email']);
            if ($validator->fails()) {
                $invalidEmail = true;
            }
        }

        $settings = \App\Models\SystemSetting::first();
        $companyAgent = Agent::find(1);
        $companyBank = $companyAgent?->bankAccount;

        return Inertia::render('RegisterAsAgent', [
            'email' => $email,
            'invalidEmail' => $invalidEmail,
            'entryFeeAgent' => $settings?->entry_fee_agent ?? 100,
            'entryFeeBusinessPartner' => $settings?->entry_fee_business_partner ?? 3000,
            'companyBank' => $companyBank ? [
                'bank_name' => $companyBank->bank_name,
                'account_name' => $companyBank->account_name,
                'account_number' => $companyBank->account_number,
            ] : null,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'profile_type' => 'required|in:individual,company',
            'individual_name' => 'required_if:profile_type,individual|nullable|string|max:255',
            'individual_phone' => 'required_if:profile_type,individual|nullable|string|max:255',
            'individual_email' => 'nullable|email|max:255',
            'individual_address' => 'required_if:profile_type,individual|nullable|string',
            'individual_id_number' => 'required_if:profile_type,individual|string|max:255',
            'individual_id_file' => 'required_if:profile_type,individual|file|mimes:pdf,jpeg,jpg,png|max:10240',
            'company_representative_name' => 'required_if:profile_type,company|string|max:255',
            'company_name' => 'required_if:profile_type,company|string|max:255',
            'company_registration_number' => 'required_if:profile_type,company|string|max:255',
            'company_address' => 'required_if:profile_type,company|string',
            'company_phone' => 'required_if:profile_type,company|string|max:255',
            'company_email_address' => 'required_if:profile_type,company|nullable|email|max:255',
            'company_reg_file' => 'required_if:profile_type,company|file|mimes:pdf,jpeg,jpg,png|max:10240',
            'about' => 'required|string|max:1000',
            'password' => [
                'required',
                'string',
                'min:12',
                'confirmed',
                // 'regex:#^(?=.*[0-9])(?=.*[!@#\\$%^&*()_+\-=\[\]{}|;:,.<>?])#'
            ],
            'referral_code' => 'nullable|string|exists:referral_codes,code',
            'terms' => 'required|accepted',
        ], [
            'password.regex' => 'Password must contain at least one number and one special character (!@#$%^&*()_+-=[]{}|;:,.<>?)',
            'password.min' => 'Password must be at least 12 characters long',
            'email.unique' => 'This email address is already registered. Please use a different email or try logging in.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
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

            // Resolve upline agent: explicit referral code → owner; otherwise default BP agent (QNA-03)
            $uplineAgent = null;
            if ($request->filled('referral_code')) {
                $uplineCode = ReferralCode::where('code', $request->referral_code)->first();
                if ($uplineCode) {
                    $uplineAgent = Agent::find($uplineCode->agent_id);
                }
            }
            if (! $uplineAgent) {
                $uplineAgent = Agent::query()
                    ->where('agent_role', Agent::ROLE_BUSINESS_PARTNER)
                    ->orderBy('id')
                    ->first();
            }

            // Create agent
            $agentData = [
                'profile_type' => $request->profile_type,
                'status' => 'inactive',
                'about' => $request->about,
                'agent_role' => Agent::ROLE_AGENT,
                'parent_agent_id' => $uplineAgent?->id,
            ];

            if ($request->profile_type === 'individual') {
                $agentData['individual_name'] = $request->individual_name;
                $agentData['individual_phone'] = $request->individual_phone;
                $agentData['individual_email'] = $request->individual_email;
                $agentData['individual_address'] = $request->individual_address;
                $agentData['individual_id_number'] = $request->individual_id_number;
            } else {
                $agentData['company_representative_name'] = $request->company_representative_name;
                $agentData['company_name'] = $request->company_name;
                $agentData['company_registration_number'] = $request->company_registration_number;
                $agentData['company_address'] = $request->company_address;
                $agentData['company_phone'] = $request->company_phone;
                $agentData['company_email_address'] = $request->company_email_address;
            }

            $agent = Agent::create($agentData);

            // Handle file uploads
            if ($request->profile_type === 'individual' && $request->hasFile('individual_id_file')) {
                $file = $request->file('individual_id_file');
                $extension = $file->getClientOriginalExtension();
                $filename = Str::random(40).'.'.$extension;
                $path = "agents/{$agent->id}/{$filename}";

                Storage::disk('local')->put($path, file_get_contents($file));
                $agent->update(['individual_id_file' => $path]);
            } elseif ($request->profile_type === 'company' && $request->hasFile('company_reg_file')) {
                $file = $request->file('company_reg_file');
                $extension = $file->getClientOriginalExtension();
                $filename = Str::random(40).'.'.$extension;
                $path = "agents/{$agent->id}/{$filename}";

                Storage::disk('local')->put($path, file_get_contents($file));
                $agent->update(['company_reg_file' => $path]);
            }

            // Log upline assignment
            if ($uplineAgent) {
                ActivityLog::logCustom($user, 'upline_assigned', "Assigned agent {$agent->id} to upline agent #{$uplineAgent->id}", $agent);
            }

            // Log agent creation
            ActivityLog::logCreate($user, $agent, $agent->toArray());

            // Send email notification (QNA-05: CC company_email_address + linked user email; dedupe)
            try {
                $recipientEmail = null;
                $ccEmails = [];

                if ($uplineAgent) {
                    $recipientEmail = $uplineAgent->company_email_address
                        ?: $uplineAgent->users()->first()?->email;
                }

                // Build CC list from agent's own contact emails
                if ($agent->company_email_address) {
                    $ccEmails[] = $agent->company_email_address;
                }
                if ($user->email) {
                    $ccEmails[] = $user->email;
                }
                $ccEmails = collect($ccEmails)
                    ->filter()
                    ->unique()
                    ->reject(fn ($e) => $e === $recipientEmail)
                    ->values()
                    ->all();

                if ($recipientEmail) {
                    $mail = Mail::to($recipientEmail);
                    if (! empty($ccEmails)) {
                        $mail->cc($ccEmails);
                    }
                    $mail->send(new \App\Mail\AgentRegisteredNotification($agent));
                }
            } catch (\Exception $e) {
                Log::error('Failed to send agent registration notification email', [
                    'agent_id' => $agent->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Create referral code for this agent using helper method
            $referralCode = $agent->createReferralCode();

            // Log referral code creation
            ActivityLog::logCreate($user, $referralCode, $referralCode->toArray());

            // Log agent referral code assignment
            ActivityLog::logCustom($user, 'referral_code_assigned', "Assigned referral code {$referralCode->code} to agent {$agent->id}");

            // Link user to agent
            $user->agents()->attach($agent->id);

            // Log user-agent relationship creation
            ActivityLog::logCustom($user, 'user_agent_linked', "Linked user {$user->email} to agent {$agent->id}");

            DB::commit();

            return back()->with('success', 'Agent registration completed successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Failed to register agent. '.$e->getMessage()])->withInput();
        }
    }

    public function verifyEmail(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        $service = app(\App\Services\RegistrationVerificationService::class);
        $verified = $service->verify($request->email, $request->code);

        if (! $verified) {
            return back()->withErrors(['code' => 'Invalid or expired verification code.']);
        }

        return back()->with('email_verified', true);
    }

    public function resendCode(\Illuminate\Http\Request $request)
    {
        $request->validate(['email' => 'required|email']);

        try {
            $service = app(\App\Services\RegistrationVerificationService::class);
            $service->resend($request->email);

            return back()->with('success', 'Verification code resent.');
        } catch (\App\Exceptions\VerificationDailyLimitException $e) {
            return back()->withErrors(['email' => 'Daily verification limit reached. Try again tomorrow.']);
        }
    }

    public function stripeSuccess(\Illuminate\Http\Request $request)
    {
        return \Inertia\Inertia::render('Agent/PaymentComplete', [
            'status' => 'success',
            'session_id' => $request->get('session_id'),
        ]);
    }

    public function stripeCancelled(\Illuminate\Http\Request $request)
    {
        return \Inertia\Inertia::render('Agent/PaymentComplete', [
            'status' => 'cancelled',
        ]);
    }

    public function completePayment(\Illuminate\Http\Request $request)
    {
        $user = auth()->user();
        $agent = $user?->agents()->first();

        return \Inertia\Inertia::render('Agent/PaymentComplete', [
            'agent' => $agent,
            'status' => 'pending',
        ]);
    }

    public function submitPayment(\Illuminate\Http\Request $request)
    {
        $user = auth()->user();
        $agent = $user?->agents()->first();

        if (! $agent) {
            return back()->withErrors(['error' => 'Agent record not found.']);
        }

        // Stripe payment initiation handled via FeeService when Cashier is integrated.
        // For now return pending confirmation.
        return back()->with('success', 'Payment request submitted. An administrator will confirm your payment.');
    }
}
