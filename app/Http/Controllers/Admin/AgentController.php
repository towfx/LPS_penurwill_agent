<?php

namespace App\Http\Controllers\Admin;

use App\Exports\AgentsExport;
use App\Http\Controllers\Controller;
use App\Mail\AccountCreatedByAdminNotification;
use App\Models\ActivityLog;
use App\Models\Agent;
use App\Models\AgentNotification;
use App\Models\FeePayment;
use App\Models\User;
use App\Services\AgentHierarchy;
use App\Services\FeeService;
use App\Services\NotificationService;
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
use Maatwebsite\Excel\Facades\Excel;

class AgentController extends Controller
{
    /**
     * Show the form for creating a new agent
     */
    public function create()
    {
        $systemSetting = \App\Models\SystemSetting::first();
        $referralCodePrefix = $systemSetting?->referral_code_prefix ?? 'REF';
        $commissionDefaultRate = $systemSetting?->commission_default_rate ? (float) $systemSetting->commission_default_rate : 0;

        return Inertia::render('Admin/AgentsAdd', [
            'referralCodePrefix' => $referralCodePrefix,
            'commissionDefaultRate' => $commissionDefaultRate,
        ]);
    }

    /**
     * Store a newly created agent
     */
    public function store(Request $request)
    {
        $adminUser = Auth::user();

        $validator = Validator::make($request->all(), [
            'profile_type' => 'required|in:individual,company',
            'individual_name' => 'required_if:profile_type,individual|nullable|string|max:255',
            'individual_phone' => 'required_if:profile_type,individual|nullable|string|max:255',
            'individual_email' => 'nullable|email|max:255',
            'individual_address' => 'required_if:profile_type,individual|nullable|string',
            'individual_id_number' => 'required_if:profile_type,individual|nullable|string|max:255',
            'individual_id_file' => 'nullable|file|mimes:pdf,jpeg,jpg,png|max:10240',
            'company_representative_name' => 'required_if:profile_type,company|nullable|string|max:255',
            'company_representative_id_number' => 'required_if:profile_type,company|nullable|string|max:255',
            'company_name' => 'required_if:profile_type,company|nullable|string|max:255',
            'company_registration_number' => 'required_if:profile_type,company|nullable|string|max:255',
            'company_address' => 'required_if:profile_type,company|nullable|string',
            'company_phone' => 'required_if:profile_type,company|nullable|string|max:255',
            'company_email_address' => 'required_if:profile_type,company|nullable|email|max:255',
            'company_reg_file' => 'nullable|file|mimes:pdf,jpeg,jpg,png|max:10240',
            'company_representative_id_file' => 'nullable|file|mimes:pdf,jpeg,jpg,png|max:10240',
            'about' => 'required|string|max:1000',
            'user_email' => 'required|email|unique:users,email',
            'user_password' => 'required|string|min:8|confirmed',
            'status' => 'required|in:active,inactive,suspended,banned',
            'agent_role' => 'nullable|in:'.implode(',', [Agent::ROLE_AGENT, Agent::ROLE_AGENT_LEADER, Agent::ROLE_BUSINESS_PARTNER]),
            'parent_agent_id' => 'nullable|integer|exists:agents,id',
            // Bank account fields
            'bank_account_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'iban' => 'nullable|string|max:255',
            'swift_code' => 'nullable|string|max:255',
            // Referral code fields
            'referral_code' => 'nullable|string|max:255|unique:referral_codes,code',
            'referral_commission_rate' => 'nullable|numeric|min:0|max:100',
            'referral_is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Validate hierarchy if parent_agent_id provided
        if ($request->filled('parent_agent_id')) {
            $hierarchy = app(AgentHierarchy::class);
            $parent = Agent::find($request->parent_agent_id);
            $tempAgent = new Agent(['agent_role' => $request->agent_role ?? Agent::ROLE_AGENT]);
            $hierarchyErrors = $hierarchy->validateHierarchyChange($tempAgent, $parent);
            if (! empty($hierarchyErrors)) {
                return back()->withErrors(['parent_agent_id' => implode(' ', $hierarchyErrors)])->withInput();
            }
        }

        DB::beginTransaction();
        try {
            // Create user
            $user = User::create([
                'name' => $request->profile_type === 'individual'
                    ? $request->individual_name
                    : $request->company_representative_name,
                'email' => $request->user_email,
                'password' => Hash::make($request->user_password),
                'email_verified_at' => now(),
            ]);

            // Log user creation
            ActivityLog::logCreate($adminUser, $user, $user->toArray());

            // Assign agent role
            $user->assignRole('agent');

            // Log role assignment
            ActivityLog::logCustom($adminUser, 'role_assigned', "Admin assigned 'agent' role to user {$user->email}", $user);

            // Create agent
            $agentData = [
                'profile_type' => $request->profile_type,
                'status' => $request->status,
                'about' => $request->about,
                'agent_role' => $request->agent_role ?? Agent::ROLE_AGENT,
                'parent_agent_id' => $request->parent_agent_id,
            ];

            if ($request->profile_type === 'individual') {
                $agentData['individual_name'] = $request->individual_name;
                $agentData['individual_phone'] = $request->individual_phone;
                $agentData['individual_email'] = $request->individual_email;
                $agentData['individual_address'] = $request->individual_address;
                $agentData['individual_id_number'] = $request->individual_id_number;
            } else {
                $agentData['company_representative_name'] = $request->company_representative_name;
                $agentData['company_representative_id_number'] = $request->company_representative_id_number;
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

            // Handle company representative ID file upload (company profile only)
            if ($request->profile_type === 'company' && $request->hasFile('company_representative_id_file')) {
                $file = $request->file('company_representative_id_file');
                $extension = $file->getClientOriginalExtension();
                $filename = Str::random(40).'.'.$extension;
                $path = "agents/{$agent->id}/{$filename}";

                Storage::disk('local')->put($path, file_get_contents($file));
                $agent->update(['company_representative_id_file' => $path]);
            }

            // Log agent creation
            ActivityLog::logCreate($adminUser, $agent, $agent->toArray());

            // Handle bank account creation
            if ($request->filled('bank_account_name') && $request->filled('bank_account_number') && $request->filled('bank_name')) {
                \App\Models\BankAccount::create([
                    'agent_id' => $agent->id,
                    'account_name' => $request->bank_account_name,
                    'account_number' => $request->bank_account_number,
                    'bank_name' => $request->bank_name,
                    'iban' => $request->iban,
                    'swift_code' => $request->swift_code,
                ]);
            }

            // Create or use provided referral code
            if ($request->filled('referral_code')) {
                $systemSetting = \App\Models\SystemSetting::first();
                $referralCode = \App\Models\ReferralCode::create([
                    'agent_id' => $agent->id,
                    'code' => $request->referral_code,
                    'is_active' => $request->referral_is_active ?? true,
                    'commission_rate' => $request->referral_commission_rate ?? $systemSetting?->commission_default_rate ?? 0,
                    'used_count' => 0,
                    'expires_at' => now()->addYears(5),
                ]);
                $agent->update(['referral_code_id' => $referralCode->id]);
            } else {
                // Generate referral code using helper method
                $referralCode = $agent->createReferralCode(
                    $request->referral_commission_rate,
                    $request->referral_is_active ?? true
                );
            }

            // Log referral code creation
            ActivityLog::logCreate($adminUser, $referralCode, $referralCode->toArray());

            // Log agent referral code assignment
            ActivityLog::logCustom($adminUser, 'referral_code_assigned', "Admin assigned referral code {$referralCode->code} to agent {$agent->id}", $agent);

            // Link user to agent
            $user->agents()->attach($agent->id);

            // Log user-agent relationship creation
            ActivityLog::logCustom($adminUser, 'user_agent_linked', "Admin linked user {$user->email} to agent {$agent->id}", $agent);

            DB::commit();

            // Send account creation email (outside transaction so email failure doesn't roll back)
            try {
                $tempPassword = $request->user_password;
                Mail::to($user->email)->send(new AccountCreatedByAdminNotification($user, $agent, $tempPassword));
            } catch (\Throwable $e) {
                Log::warning('AgentController: AccountCreatedByAdminNotification failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return redirect()->route('admin.agents.list')->with('success', 'Agent created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Failed to create agent. '.$e->getMessage()])->withInput();
        }
    }

    /**
     * Display the specified agent
     */
    public function show($id)
    {
        $agent = Agent::with(['users', 'bankAccount', 'referralCode', 'parentAgent', 'feePayments' => function($q) {
            $q->latest();
        }])->findOrFail($id);

        return Inertia::render('Admin/AgentView', [
            'agent' => [
                'id' => $agent->id,
                'profile_type' => $agent->profile_type,
                'individual_name' => $agent->individual_name,
                'individual_phone' => $agent->individual_phone,
                'individual_address' => $agent->individual_address,
                'individual_id_number' => $agent->individual_id_number,
                'individual_id_file' => $agent->individual_id_file,
                'company_representative_name' => $agent->company_representative_name,
                'company_representative_id_number' => $agent->company_representative_id_number,
                'company_name' => $agent->company_name,
                'company_registration_number' => $agent->company_registration_number,
                'company_address' => $agent->company_address,
                'company_phone' => $agent->company_phone,
                'company_reg_file' => $agent->company_reg_file,
                'company_representative_id_file' => $agent->company_representative_id_file,
                'status' => $agent->status,
                'agent_role' => $agent->agent_role,
                'parent_agent_id' => $agent->parent_agent_id,
                'parent_agent' => $agent->parentAgent ? [
                    'id' => $agent->parentAgent->id,
                    'name' => $agent->parentAgent->name,
                    'agent_role' => $agent->parentAgent->agent_role,
                ] : null,
                'fee_payment_status' => $agent->fee_payment_status,
                'registered_at' => $agent->registered_at?->toDateString(),
                'expires_at' => $agent->expires_at?->toDateString(),
                'renewal_due_at' => $agent->renewal_due_at?->toDateString(),
                'subordinates_count' => $agent->subordinates()->count(),
                'created_at' => $agent->created_at->format('Y-m-d H:i:s'),
                'user_email' => $agent->users->first()?->email,
                'bank_account' => $agent->bankAccount,
                'referral_code' => $agent->referralCode,
                'latest_fee_payment' => $agent->feePayments->first() ? [
                    'id' => $agent->feePayments->first()->id,
                    'fee_type' => $agent->feePayments->first()->fee_type,
                    'amount' => $agent->feePayments->first()->amount,
                    'payment_method' => $agent->feePayments->first()->payment_method,
                    'payment_reference' => $agent->feePayments->first()->payment_reference,
                    'receipt_file' => $agent->feePayments->first()->receipt_file,
                    'paid_at' => $agent->feePayments->first()->paid_at?->format('Y-m-d H:i:s'),
                ] : null,
            ],
        ]);
    }

    /**
     * Show the form for editing the specified agent
     */
    public function edit($id)
    {
        $agent = Agent::with(['users', 'bankAccount', 'referralCode'])->findOrFail($id);

        return Inertia::render('Admin/AgentUpdate', [
            'id' => $id,
            'agent' => [
                'id' => $agent->id,
                'profile_type' => $agent->profile_type,
                'individual_name' => $agent->individual_name,
                'individual_phone' => $agent->individual_phone,
                'individual_address' => $agent->individual_address,
                'individual_id_number' => $agent->individual_id_number,
                'individual_id_file' => $agent->individual_id_file,
                'company_representative_name' => $agent->company_representative_name,
                'company_representative_id_number' => $agent->company_representative_id_number,
                'company_name' => $agent->company_name,
                'company_registration_number' => $agent->company_registration_number,
                'company_address' => $agent->company_address,
                'company_phone' => $agent->company_phone,
                'company_reg_file' => $agent->company_reg_file,
                'company_representative_id_file' => $agent->company_representative_id_file,
                'status' => $agent->status,
                'agent_role' => $agent->agent_role,
                'parent_agent_id' => $agent->parent_agent_id,
                'fee_payment_status' => $agent->fee_payment_status,
                'registered_at' => $agent->registered_at?->toDateString(),
                'expires_at' => $agent->expires_at?->toDateString(),
                'renewal_due_at' => $agent->renewal_due_at?->toDateString(),
                'subordinates_count' => $agent->subordinates()->count(),
                'user_email' => $agent->users->first()?->email,
                'bank_account' => $agent->bankAccount,
                'referral_code' => $agent->referralCode,
            ],
        ]);
    }

    /**
     * Update the specified agent
     */
    public function update(Request $request, $id)
    {
        $adminUser = Auth::user();
        $agent = Agent::findOrFail($id);
        $user = $agent->users->first();

        // Capture before data for activity logging
        $beforeData = $agent->toArray();
        if ($agent->bankAccount) {
            $beforeData['bank_account'] = $agent->bankAccount->toArray();
        }
        if ($agent->referralCode) {
            $beforeData['referral_code'] = $agent->referralCode->toArray();
        }

        // Build validation rules based on profile type
        $rules = [
            'profile_type' => 'required|in:individual,company',
            'about' => 'nullable|string|max:1000',
            'user_password' => 'nullable|string|min:8|confirmed',
            'status' => 'required|in:active,inactive,suspended,banned,expired',
            'agent_role' => 'nullable|in:'.implode(',', [Agent::ROLE_AGENT, Agent::ROLE_AGENT_LEADER, Agent::ROLE_BUSINESS_PARTNER]),
            'parent_agent_id' => 'nullable|integer|exists:agents,id',
            'confirm_downgrade' => 'sometimes|boolean',
            // Bank account fields
            'bank_account_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'iban' => 'nullable|string|max:255',
            'swift_code' => 'nullable|string|max:255',
            // Referral code fields
            'referral_code' => 'nullable|string|max:255|unique:referral_codes,code,'.($agent->referralCode->id ?? 'NULL').',id',
            'referral_commission_rate' => 'nullable|numeric|min:0|max:100',
            'referral_is_active' => 'nullable|boolean',
        ];

        // Add validation rules based on profile type
        if ($request->profile_type === 'individual') {
            $rules['individual_name'] = 'required|string|max:255';
            $rules['individual_phone'] = 'required|string|max:255';
            $rules['individual_email'] = 'nullable|email|max:255';
            $rules['individual_address'] = 'required|string';
            $rules['individual_id_number'] = 'required|string|max:255';
            $rules['individual_id_file'] = 'nullable|file|mimes:pdf,jpeg,jpg,png|max:10240';
        } else {
            $rules['company_representative_name'] = 'required|string|max:255';
            $rules['company_representative_id_number'] = 'required|string|max:255';
            $rules['company_name'] = 'required|string|max:255';
            $rules['company_registration_number'] = 'required|string|max:255';
            $rules['company_address'] = 'required|string';
            $rules['company_phone'] = 'required|string|max:255';
            $rules['company_email_address'] = 'required_if:profile_type,company|nullable|email|max:255';
            $rules['company_reg_file'] = 'nullable|file|mimes:pdf,jpeg,jpg,png|max:10240';
            $rules['company_representative_id_file'] = 'nullable|file|mimes:pdf,jpeg,jpg,png|max:10240';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Hierarchy + downgrade checks (Decision 20)
        $hierarchy = app(AgentHierarchy::class);
        $newRole = $request->agent_role ?? $agent->agent_role ?? Agent::ROLE_AGENT;
        $currentRole = $agent->agent_role ?? Agent::ROLE_AGENT;
        $newRank = AgentHierarchy::ROLE_RANK[$newRole] ?? 0;
        $currentRank = AgentHierarchy::ROLE_RANK[$currentRole] ?? 0;

        if ($newRank < $currentRank && $agent->subordinates()->count() > 0 && ! $request->boolean('confirm_downgrade')) {
            return response()->json([
                'downgrade_warning' => true,
                'subordinate_count' => $agent->subordinates()->count(),
                'message' => "Downgrading this agent's role will leave {$agent->subordinates()->count()} subordinate(s) reassigned. Confirm to proceed.",
            ], 422);
        }

        if ($request->has('parent_agent_id')) {
            $parent = $request->filled('parent_agent_id') ? Agent::find($request->parent_agent_id) : null;
            $candidate = clone $agent;
            $candidate->agent_role = $newRole;
            $hierarchyErrors = $hierarchy->validateHierarchyChange($candidate, $parent);
            if (! empty($hierarchyErrors)) {
                return back()->withErrors(['parent_agent_id' => implode(' ', $hierarchyErrors)])->withInput();
            }
        }

        DB::beginTransaction();
        try {
            // Update agent
            $agentData = [
                'profile_type' => $request->profile_type,
                'status' => $request->status,
                'about' => $request->about,
            ];

            if ($request->filled('agent_role')) {
                $agentData['agent_role'] = $request->agent_role;
            }
            if ($request->has('parent_agent_id')) {
                $agentData['parent_agent_id'] = $request->parent_agent_id ?: null;
            }

            if ($request->profile_type === 'individual') {
                $agentData['individual_name'] = $request->individual_name;
                $agentData['individual_phone'] = $request->individual_phone;
                $agentData['individual_email'] = $request->individual_email;
                $agentData['individual_address'] = $request->individual_address;
                $agentData['individual_id_number'] = $request->individual_id_number;
                // Clear company fields and delete old file if exists
                $agentData['company_representative_name'] = null;
                $agentData['company_representative_id_number'] = null;
                $agentData['company_name'] = null;
                $agentData['company_registration_number'] = null;
                $agentData['company_address'] = null;
                $agentData['company_phone'] = null;
                $agentData['company_email_address'] = null;
                if ($agent->company_reg_file && Storage::disk('local')->exists($agent->company_reg_file)) {
                    Storage::disk('local')->delete($agent->company_reg_file);
                }
                if ($agent->company_representative_id_file && Storage::disk('local')->exists($agent->company_representative_id_file)) {
                    Storage::disk('local')->delete($agent->company_representative_id_file);
                }
                $agentData['company_reg_file'] = null;
                $agentData['company_representative_id_file'] = null;
            } else {
                $agentData['company_representative_name'] = $request->company_representative_name;
                $agentData['company_representative_id_number'] = $request->company_representative_id_number;
                $agentData['company_name'] = $request->company_name;
                $agentData['company_registration_number'] = $request->company_registration_number;
                $agentData['company_address'] = $request->company_address;
                $agentData['company_phone'] = $request->company_phone;
                $agentData['company_email_address'] = $request->company_email_address;
                // Clear individual fields and delete old file if exists
                $agentData['individual_name'] = null;
                $agentData['individual_phone'] = null;
                $agentData['individual_address'] = null;
                $agentData['individual_id_number'] = null;
                if ($agent->individual_id_file && Storage::disk('local')->exists($agent->individual_id_file)) {
                    Storage::disk('local')->delete($agent->individual_id_file);
                }
                $agentData['individual_id_file'] = null;
            }

            $agent->update($agentData);

            // Handle file uploads
            if ($request->profile_type === 'individual' && $request->hasFile('individual_id_file')) {
                // Delete old file if exists
                if ($agent->individual_id_file && Storage::disk('local')->exists($agent->individual_id_file)) {
                    Storage::disk('local')->delete($agent->individual_id_file);
                }

                $file = $request->file('individual_id_file');
                $extension = $file->getClientOriginalExtension();
                $filename = Str::random(40).'.'.$extension;
                $path = "agents/{$agent->id}/{$filename}";

                Storage::disk('local')->put($path, file_get_contents($file));
                $agent->update(['individual_id_file' => $path]);
            } elseif ($request->profile_type === 'company' && $request->hasFile('company_reg_file')) {
                // Delete old file if exists
                if ($agent->company_reg_file && Storage::disk('local')->exists($agent->company_reg_file)) {
                    Storage::disk('local')->delete($agent->company_reg_file);
                }

                $file = $request->file('company_reg_file');
                $extension = $file->getClientOriginalExtension();
                $filename = Str::random(40).'.'.$extension;
                $path = "agents/{$agent->id}/{$filename}";

                Storage::disk('local')->put($path, file_get_contents($file));
                $agent->update(['company_reg_file' => $path]);
            }

            // Handle company representative ID file upload (company profile only)
            if ($request->profile_type === 'company' && $request->hasFile('company_representative_id_file')) {
                if ($agent->company_representative_id_file && Storage::disk('local')->exists($agent->company_representative_id_file)) {
                    Storage::disk('local')->delete($agent->company_representative_id_file);
                }

                $file = $request->file('company_representative_id_file');
                $extension = $file->getClientOriginalExtension();
                $filename = Str::random(40).'.'.$extension;
                $path = "agents/{$agent->id}/{$filename}";

                Storage::disk('local')->put($path, file_get_contents($file));
                $agent->update(['company_representative_id_file' => $path]);
            }

            // Update user if password is provided
            if ($request->filled('user_password')) {
                $user->update([
                    'password' => Hash::make($request->user_password),
                ]);

                // Log password update
                ActivityLog::logCustom($adminUser, 'password_updated', "Admin updated password for user {$user->email}", $user);
            }

            // Update or create bank account only if account number is provided
            if ($request->filled('bank_account_number')) {
                if ($agent->bankAccount) {
                    $agent->bankAccount->update([
                        'account_name' => $request->bank_account_name,
                        'account_number' => $request->bank_account_number,
                        'bank_name' => $request->bank_name,
                        'iban' => $request->iban,
                        'swift_code' => $request->swift_code,
                    ]);
                } else {
                    $bankAccount = $agent->bankAccount()->create([
                        'account_name' => $request->bank_account_name,
                        'account_number' => $request->bank_account_number,
                        'bank_name' => $request->bank_name,
                        'iban' => $request->iban,
                        'swift_code' => $request->swift_code,
                    ]);

                    // Log bank account creation
                    ActivityLog::logCreate($adminUser, $bankAccount, $bankAccount->toArray());
                }
            }

            // Update referral code
            if ($agent->referralCode) {
                $agent->referralCode->update([
                    'code' => $request->referral_code,
                    'commission_rate' => $request->referral_commission_rate,
                    'is_active' => $request->referral_is_active,
                ]);
            } elseif ($request->referral_code) {
                // Create new referral code if agent doesn't have one
                $systemSetting = \App\Models\SystemSetting::first();
                $referralCode = \App\Models\ReferralCode::create([
                    'agent_id' => $agent->id,
                    'code' => $request->referral_code,
                    'is_active' => $request->referral_is_active ?? true,
                    'commission_rate' => $request->referral_commission_rate ?? $systemSetting->commission_default_rate,
                    'used_count' => 0,
                    'expires_at' => now()->addYears(5),
                ]);
                $agent->update(['referral_code_id' => $referralCode->id]);

                // Log referral code creation
                ActivityLog::logCreate($adminUser, $referralCode, $referralCode->toArray());
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

            // Log the agent update activity
            ActivityLog::logUpdate($adminUser, $agent, $beforeData, $afterData);

            DB::commit();

            return redirect()->route('admin.agents.list')->with('success', 'Agent updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Failed to update agent. '.$e->getMessage()])->withInput();
        }
    }

    /**
     * Download agent file
     */
    public function downloadFile($id, $field)
    {
        $agent = Agent::findOrFail($id);

        $allowedFields = ['individual_id_file', 'company_reg_file', 'company_representative_id_file', 'receipt_file'];
        if (! in_array($field, $allowedFields)) {
            abort(404);
        }

        // Special case for receipt_file which is on FeePayment model, not Agent model directly
        if ($field === 'receipt_file') {
            $latestPayment = $agent->feePayments()->whereNotNull('receipt_file')->latest()->first();
            $filePath = $latestPayment?->receipt_file;
        } else {
            $filePath = $agent->$field;
        }
        if (! $filePath || ! Storage::disk('local')->exists($filePath)) {
            abort(404);
        }

        return response()->download(Storage::disk('local')->path($filePath));
    }

    /**
     * Approve agent application
     */
    public function approve($id, FeeService $feeService, NotificationService $notificationService)
    {
        $adminUser = Auth::user();
        $agent = Agent::findOrFail($id);

        DB::beginTransaction();
        try {
            $beforeData = $agent->toArray();

            $agent->update(['status' => 'active']);

            // Decision 22: if fee is already paid, record an entry-fee row.
            // Otherwise mark fee as waived and set the lifecycle dates manually.
            if ($agent->fee_payment_status === Agent::FEE_STATUS_PAID) {
                $feeService->applyEntryFee($agent, $adminUser);
            } else {
                $duration = (int) (\App\Models\SystemSetting::first()?->membership_duration_days ?? 365);
                $reminder = (int) (\App\Models\SystemSetting::first()?->renewal_reminder_days_before ?? 30);
                $agent->update([
                    'fee_payment_status' => Agent::FEE_STATUS_WAIVED,
                    'registered_at' => now()->toDateString(),
                    'expires_at' => now()->addDays($duration)->toDateString(),
                    'renewal_due_at' => now()->addDays(max(1, $duration - $reminder))->toDateString(),
                ]);
            }

            $afterData = $agent->fresh()->toArray();
            ActivityLog::logUpdate($adminUser, $agent, $beforeData, $afterData);
            ActivityLog::logCustom($adminUser, 'agent_approved', "Admin approved agent application for agent {$agent->id}", $agent);

            DB::commit();

            // Notify agent of approval
            $notificationService->notify(
                $agent,
                AgentNotification::TYPE_AGENT_APPROVED,
                'Application Approved',
                'Congratulations! Your agent application has been approved. You can now access your dashboard.',
                Agent::class,
                $agent->id,
            );

            // Notify parent of new team member
            if ($agent->parent_agent_id) {
                $parent = Agent::find($agent->parent_agent_id);
                if ($parent) {
                    $notificationService->notify(
                        $parent,
                        AgentNotification::TYPE_NEW_TEAM_MEMBER,
                        'New Team Member',
                        "A new agent ({$agent->name}) has joined your team.",
                        Agent::class,
                        $agent->id,
                    );
                }
            }

            return redirect()->route('admin.agents.list')->with('success', 'Agent approved successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Failed to approve agent. '.$e->getMessage()]);
        }
    }

    /**
     * Reject an agent application.
     * If the agent has a paid fee, returns has_paid_fee flag for Vue confirmation modal.
     */
    public function reject(Request $request, $id, NotificationService $notificationService)
    {
        $request->validate(['rejection_reason' => 'nullable|string|max:1000']);

        $adminUser = Auth::user();
        $agent = Agent::findOrFail($id);

        $hasPaidFee = $agent->fee_payment_status === Agent::FEE_STATUS_PAID
            || FeePayment::where('agent_id', $agent->id)->exists();

        if ($hasPaidFee && ! $request->boolean('confirm_rejection')) {
            return response()->json([
                'has_paid_fee' => true,
                'message' => '⚠ This agent has a fee payment on record. Please process a refund via the payment dashboard before confirming rejection.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $beforeData = $agent->toArray();
            $agent->update([
                'status' => 'rejected',
                'rejection_reason' => $request->rejection_reason,
            ]);
            ActivityLog::logUpdate($adminUser, $agent, $beforeData, $agent->toArray());
            ActivityLog::logCustom($adminUser, 'agent_rejected', "Admin rejected agent #{$agent->id}", $agent);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Failed to reject agent. '.$e->getMessage()]);
        }

        $notificationService->notify(
            $agent,
            AgentNotification::TYPE_AGENT_REJECTED,
            'Application Rejected',
            $request->rejection_reason
                ? "Your application was not approved: {$request->rejection_reason}"
                : 'Your application was not approved at this time.',
            Agent::class,
            $agent->id,
        );

        return redirect()->route('admin.agents.list')->with('success', 'Agent rejected.');
    }

    /**
     * Agent-side: reset status to pending and notify admin.
     */
    public function requestApproval(Request $request, NotificationService $notificationService)
    {
        $agent = auth()->user()->agents()->first();
        if (! $agent) {
            return response()->json(['error' => 'Agent not found.'], 404);
        }

        $agent->update(['status' => 'pending']);

        $notificationService->notifyAdmin(
            AgentNotification::TYPE_APPROVAL_REQUESTED,
            "Re-approval Request — {$agent->name}",
            "Agent #{$agent->id} ({$agent->name}) has requested re-approval.",
            Agent::class,
            $agent->id,
        );

        return back()->with('success', 'Re-approval request submitted.');
    }

    /**
     * JSON endpoint listing agents eligible to be a parent based on child_role.
     */
    public function parents(Request $request)
    {
        $query = Agent::query();

        $childRole = $request->query('child_role');
        if ($childRole === Agent::ROLE_BUSINESS_PARTNER) {
            $query->where('id', 1);
        } elseif ($childRole === Agent::ROLE_AGENT_LEADER) {
            $query->where('agent_role', Agent::ROLE_BUSINESS_PARTNER);
        } elseif ($childRole === Agent::ROLE_AGENT) {
            $query->where('agent_role', Agent::ROLE_AGENT_LEADER);
        } else {
            $query->whereIn('agent_role', [Agent::ROLE_AGENT_LEADER, Agent::ROLE_BUSINESS_PARTNER]);
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function($q) use ($search) {
                $q->where('individual_name', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('id', $search);
            });
        }

        $agents = $query->orderBy('agent_role')
            ->orderBy('id')
            ->get(['id', 'agent_role', 'company_name', 'individual_name', 'profile_type']);

        $agents->each(function ($agent) {
            $agent->name = $agent->name;
        });

        return response()->json($agents);
    }

    /**
     * Export agents data to Excel
     */
    public function export()
    {
        return Excel::download(new AgentsExport, 'agents.xls');
    }

    /**
     * Show agent hierarchy
     */
    public function hierarchy()
    {
        $allAgents = Agent::where('status', 'active')->get();

        $data = $allAgents->map(function ($agent) {
            return [
                'id' => (string) $agent->id,
                'parentId' => $agent->parent_agent_id ? (string) $agent->parent_agent_id : "",
                'name' => $agent->name,
                'title' => strtoupper(str_replace('_', ' ', $agent->agent_role ?? 'agent')),
                'imageUrl' => $agent->profile_image ? \Illuminate\Support\Facades\Storage::url($agent->profile_image) : null,
            ];
        });

        return Inertia::render('Admin/AgentHierarchy', [
            'hierarchyData' => $data
        ]);
    }
}
