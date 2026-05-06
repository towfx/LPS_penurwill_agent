<?php

namespace App\Http\Controllers;

use App\Mail\AccountCreatedNotification;
use App\Models\ActivityLog;
use App\Models\Agent;
use App\Models\BankAccount;
use App\Models\FeePayment;
use App\Models\ReferralCode;
use App\Models\User;
use App\Services\FeeService;
use App\Services\RegistrationVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    /**
     * Legacy single-form store (unused by new wizard; kept for back-compat).
     */
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
            'about' => 'nullable|string|max:1000',
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'referral_code' => 'nullable|string|exists:referral_codes,code',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $request->profile_type === 'individual'
                    ? $request->individual_name
                    : $request->company_representative_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'email_verified_at' => now(),
            ]);

            ActivityLog::logCreate($user, $user, $user->toArray());
            $user->assignRole('agent');

            $uplineAgent = $this->resolveUpline($request->referral_code);

            $agentData = [
                'profile_type' => $request->profile_type,
                'status' => 'pending',
                'about' => $request->about,
                'agent_role' => Agent::ROLE_AGENT,
                'parent_agent_id' => $uplineAgent?->id,
                'fee_payment_status' => Agent::FEE_STATUS_PENDING,
            ];

            if ($request->profile_type === 'individual') {
                $agentData += [
                    'individual_name' => $request->individual_name,
                    'individual_phone' => $request->individual_phone,
                    'individual_address' => $request->individual_address,
                    'individual_id_number' => $request->individual_id_number,
                ];
            } else {
                $agentData += [
                    'company_representative_name' => $request->company_representative_name,
                    'company_name' => $request->company_name,
                    'company_registration_number' => $request->company_registration_number,
                    'company_address' => $request->company_address,
                    'company_phone' => $request->company_phone,
                    'company_email_address' => $request->company_email_address,
                ];
            }

            $agent = Agent::create($agentData);

            if ($request->profile_type === 'individual' && $request->hasFile('individual_id_file')) {
                $path = $this->storeFile($request->file('individual_id_file'), $agent->id);
                $agent->update(['individual_id_file' => $path]);
            } elseif ($request->profile_type === 'company' && $request->hasFile('company_reg_file')) {
                $path = $this->storeFile($request->file('company_reg_file'), $agent->id);
                $agent->update(['company_reg_file' => $path]);
            }

            $referralCode = $agent->createReferralCode();
            $user->agents()->attach($agent->id);

            ActivityLog::logCreate($user, $agent, $agent->toArray());
            ActivityLog::logCreate($user, $referralCode, $referralCode->toArray());

            DB::commit();

            return back()->with('success', 'Agent registration completed successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Failed to register agent. '.$e->getMessage()])->withInput();
        }
    }

    /**
     * Step 3 → save profile + files to session so verifyEmail can create User+Agent.
     */
    public function saveDraft(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $email = strtolower(trim($request->input('email')));

        // Persist text fields (excluding password — stored separately encrypted)
        $textFields = $request->except(['individual_id_file', 'company_reg_file', 'company_representative_id_file', '_token', 'password', 'password_confirmation']);
        $textFields['email'] = $email;

        // Store password hash so it's never in plaintext in session
        if ($request->filled('password')) {
            $textFields['password_hash'] = Hash::make($request->input('password'));
        }

        session(["reg_draft.{$email}" => $textFields]);

        // Upload files to temp storage
        $filePaths = session("reg_draft_files.{$email}", []);
        foreach (['individual_id_file', 'company_reg_file', 'company_representative_id_file'] as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $ext = $file->getClientOriginalExtension();
                $tempPath = "registration-drafts/{$email}/{$field}.{$ext}";
                Storage::disk('local')->put($tempPath, file_get_contents($file));
                $filePaths[$field] = $tempPath;
            }
        }
        session(["reg_draft_files.{$email}" => $filePaths]);

        return response()->json(['success' => true]);
    }

    /**
     * Step 4 → verify code, then create User + Agent from session draft.
     */
    public function verifyEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        $email = strtolower(trim($request->input('email')));
        $service = app(RegistrationVerificationService::class);

        if (! $service->verify($email, $request->input('code'))) {
            return response()->json([
                'errors' => ['code' => ['Invalid or expired verification code.']],
            ], 422);
        }

        // Skip creation if user already exists (resend path after creation)
        if (User::where('email', $email)->exists()) {
            return response()->json(['success' => true]);
        }

        $draft = session("reg_draft.{$email}");
        $fileDraft = session("reg_draft_files.{$email}", []);

        if (! $draft) {
            return response()->json([
                'errors' => ['code' => ['Registration session expired. Please go back and re-submit your details.']],
            ], 422);
        }

        DB::transaction(function () use ($email, $draft, $fileDraft) {
            $profileType = $draft['profile_type'] ?? 'individual';
            $agentRole = ($draft['package'] ?? 'agent') === 'business_partner'
                ? Agent::ROLE_BUSINESS_PARTNER
                : Agent::ROLE_AGENT;

            $userName = $profileType === 'individual'
                ? ($draft['individual_name'] ?? $email)
                : ($draft['company_representative_name'] ?? $email);

            $user = User::create([
                'name' => $userName,
                'email' => $email,
                'password' => $draft['password_hash'] ?? Hash::make(Str::random(32)),
                'email_verified_at' => now(),
            ]);

            $user->assignRole('agent');
            ActivityLog::logCreate($user, $user, $user->toArray());

            $uplineAgent = $this->resolveUpline($draft['referral_code'] ?? null);

            $agentData = [
                'profile_type' => $profileType,
                'status' => 'pending',
                'agent_role' => $agentRole,
                'parent_agent_id' => $uplineAgent?->id,
                'fee_payment_status' => Agent::FEE_STATUS_PENDING,
            ];

            if ($profileType === 'individual') {
                $agentData += [
                    'individual_name' => $draft['individual_name'] ?? null,
                    'individual_phone' => $draft['individual_phone'] ?? null,
                    'individual_address' => $draft['individual_address'] ?? null,
                    'individual_id_number' => $draft['individual_id_number'] ?? null,
                ];
            } else {
                $agentData += [
                    'company_name' => $draft['company_name'] ?? null,
                    'company_registration_number' => $draft['company_registration_number'] ?? null,
                    'company_address' => $draft['company_address'] ?? null,
                    'company_phone' => $draft['company_phone'] ?? null,
                    'company_email_address' => $draft['company_email_address'] ?? null,
                    'company_representative_name' => $draft['company_representative_name'] ?? null,
                    'company_representative_id_number' => $draft['company_representative_id_number'] ?? null,
                ];
            }

            $agent = Agent::create($agentData);

            // Move temp files to permanent storage
            foreach ($fileDraft as $field => $tempPath) {
                if (Storage::disk('local')->exists($tempPath)) {
                    $ext = pathinfo($tempPath, PATHINFO_EXTENSION);
                    $permPath = "agents/{$agent->id}/{$field}.{$ext}";
                    Storage::disk('local')->move($tempPath, $permPath);
                    $agent->update([$field => $permPath]);
                }
            }

            // Create bank account if data provided
            if (! empty($draft['bank_name']) && ! empty($draft['bank_account_number'])) {
                BankAccount::create([
                    'agent_id' => $agent->id,
                    'bank_name' => $draft['bank_name'],
                    'account_name' => $draft['bank_account_name'] ?? '',
                    'account_number' => $draft['bank_account_number'],
                ]);
            }

            $referralCode = $agent->createReferralCode();
            $user->agents()->attach($agent->id);

            ActivityLog::logCreate($user, $agent, $agent->toArray());
            ActivityLog::logCreate($user, $referralCode, $referralCode->toArray());

            // Clear session draft
            session()->forget(["reg_draft.{$email}", "reg_draft_files.{$email}"]);

            // Send account created notification
            try {
                Mail::to($email)->send(new AccountCreatedNotification($agent));
            } catch (\Throwable $e) {
                Log::warning('AgentRegistrationController: account created email failed', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        });

        return response()->json(['success' => true]);
    }

    /**
     * Step 3 → resend verification code.
     */
    public function resendCode(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        try {
            $service = app(RegistrationVerificationService::class);
            $service->resend($request->email);

            return response()->json(['success' => true]);
        } catch (\App\Exceptions\VerificationDailyLimitException $e) {
            return response()->json([
                'errors' => ['email' => ['Daily verification limit reached. Try again tomorrow.']],
            ], 422);
        }
    }

    /**
     * Step 5 → initiate Stripe Checkout; returns {url} or {} if Stripe not configured.
     */
    public function initiateStripe(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'package' => 'required|in:agent,business_partner',
        ]);

        $email = strtolower(trim($request->input('email')));
        $user = User::where('email', $email)->first();
        $agent = $user?->agents()->first();

        if (! $agent) {
            return response()->json(['error' => 'Agent record not found.'], 404);
        }

        $agent->update(['tc_accepted_at' => now()]);

        $successUrl = url('/register-as-agent/payment/success?session_id={CHECKOUT_SESSION_ID}');
        $cancelUrl = url('/register-as-agent/payment/cancelled');

        $checkoutUrl = app(FeeService::class)->createCheckoutSession($agent, $successUrl, $cancelUrl);

        if ($checkoutUrl) {
            return response()->json(['url' => $checkoutUrl]);
        }

        // Stripe not configured — Vue advances to confirmation step
        return response()->json([]);
    }

    /**
     * Step 5 → submit manual bank transfer receipt.
     */
    public function submitManualPayment(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'receipt_file' => 'required|file|mimes:pdf,jpeg,jpg,png|max:5120',
        ]);

        $email = strtolower(trim($request->input('email')));
        $user = User::where('email', $email)->first();
        $agent = $user?->agents()->first();

        if (! $agent) {
            return response()->json(['message' => 'Agent record not found.'], 404);
        }

        $file = $request->file('receipt_file');
        $ext = $file->getClientOriginalExtension();
        $filename = Str::random(40).'.'.$ext;
        $path = "agents/{$agent->id}/receipts/{$filename}";
        Storage::disk('local')->put($path, file_get_contents($file));

        $agent->update([
            'tc_accepted_at' => now(),
            'fee_payment_status' => Agent::FEE_STATUS_PENDING,
        ]);

        $feeService = app(FeeService::class);
        FeePayment::create([
            'agent_id' => $agent->id,
            'fee_type' => FeePayment::TYPE_ENTRY,
            'role' => $agent->agent_role ?? Agent::ROLE_AGENT,
            'amount' => $feeService->getFeeAmountFor($agent->agent_role ?? Agent::ROLE_AGENT, FeePayment::TYPE_ENTRY),
            'payment_method' => FeePayment::METHOD_BANK_TRANSFER,
            'payment_reference' => $request->input('reference'),
            'receipt_file' => $path,
            'paid_at' => null,
            'recorded_by' => null,
        ]);

        try {
            ActivityLog::logCustom(
                $user,
                'manual_payment_submitted',
                "Agent #{$agent->id} submitted bank transfer receipt",
                $agent,
            );
        } catch (\Throwable) {
        }

        return response()->json(['success' => true]);
    }

    /**
     * Step 5 → skip payment; auto-login and redirect to dashboard.
     */
    public function skipPayment(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $email = strtolower(trim($request->input('email')));
        $user = User::where('email', $email)->first();
        $agent = $user?->agents()->first();

        if (! $user || ! $agent) {
            return response()->json(['message' => 'Account not found.'], 404);
        }

        $agent->update([
            'tc_accepted_at' => now(),
            'fee_payment_status' => Agent::FEE_STATUS_PENDING,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['redirect' => route('agent.dashboard')]);
    }

    /**
     * Stripe success callback (GET).
     */
    public function stripeSuccess(Request $request)
    {
        $sessionId = $request->get('session_id');

        // Retrieve session and record fee payment if not already recorded
        if ($sessionId && config('cashier.secret')) {
            try {
                $session = \Laravel\Cashier\Cashier::stripe()->checkout->sessions->retrieve($sessionId);

                if ($session->payment_status === 'paid') {
                    $agentId = $session->metadata->agent_id ?? null;
                    if ($agentId) {
                        $agent = Agent::find($agentId);
                        if ($agent && $agent->fee_payment_status !== Agent::FEE_STATUS_PAID) {
                            $systemUser = \App\Support\SystemUser::resolve();
                            app(FeeService::class)->applyEntryFee(
                                $agent,
                                $systemUser,
                                FeePayment::METHOD_STRIPE,
                                $sessionId,
                            );
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::error('stripeSuccess: session retrieval failed', ['error' => $e->getMessage()]);
            }
        }

        return Inertia::render('Agent/PaymentComplete', [
            'status' => 'success',
            'session_id' => $sessionId,
        ]);
    }

    /**
     * Stripe cancel callback (GET).
     */
    public function stripeCancelled(Request $request)
    {
        return Inertia::render('Agent/PaymentComplete', [
            'status' => 'cancelled',
        ]);
    }

    /**
     * Authenticated agent — resume/complete payment from dashboard.
     */
    public function completePayment(Request $request)
    {
        $user = auth()->user();
        $agent = $user?->agents()->first();

        $settings = \App\Models\SystemSetting::first();
        $companyAgent = Agent::find(1);
        $companyBank = $companyAgent?->bankAccount;

        return Inertia::render('Agent/PaymentComplete', [
            'agent' => $agent,
            'status' => 'pending',
            'entryFeeAgent' => $settings?->entry_fee_agent ?? 100,
            'entryFeeBusinessPartner' => $settings?->entry_fee_business_partner ?? 3000,
            'companyBank' => $companyBank ? [
                'bank_name' => $companyBank->bank_name,
                'account_name' => $companyBank->account_name,
                'account_number' => $companyBank->account_number,
            ] : null,
        ]);
    }

    /**
     * Authenticated agent — submit payment from dashboard.
     */
    public function submitPayment(Request $request)
    {
        $user = auth()->user();
        $agent = $user?->agents()->first();

        if (! $agent) {
            return back()->withErrors(['error' => 'Agent record not found.']);
        }

        return back()->with('success', 'Payment request submitted. An administrator will confirm your payment.');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function resolveUpline(?string $referralCode): ?Agent
    {
        if ($referralCode) {
            $code = ReferralCode::where('code', $referralCode)->first();
            if ($code) {
                $agent = Agent::find($code->agent_id);
                if ($agent) {
                    return $agent;
                }
            }
        }

        return Agent::where('agent_role', Agent::ROLE_BUSINESS_PARTNER)
            ->orderBy('id')
            ->first();
    }

    private function storeFile(\Illuminate\Http\UploadedFile $file, int $agentId): string
    {
        $ext = $file->getClientOriginalExtension();
        $filename = Str::random(40).'.'.$ext;
        $path = "agents/{$agentId}/{$filename}";
        Storage::disk('local')->put($path, file_get_contents($file));

        return $path;
    }
}
