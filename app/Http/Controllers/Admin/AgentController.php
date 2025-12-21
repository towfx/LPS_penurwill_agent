<?php

namespace App\Http\Controllers\Admin;

use App\Exports\AgentsExport;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Agent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class AgentController extends Controller
{
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
            'company_name' => 'required_if:profile_type,company|nullable|string|max:255',
            'company_registration_number' => 'required_if:profile_type,company|nullable|string|max:255',
            'company_address' => 'required_if:profile_type,company|nullable|string',
            'company_phone' => 'required_if:profile_type,company|nullable|string|max:255',
            'company_email_address' => 'required_if:profile_type,company|nullable|email|max:255',
            'company_reg_file' => 'nullable|file|mimes:pdf,jpeg,jpg,png|max:10240',
            'about' => 'required|string|max:1000',
            'user_email' => 'required|email|unique:users,email',
            'user_password' => 'required|string|min:8|confirmed',
            'status' => 'required|in:active,inactive,suspended,banned',
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

            // Log agent creation
            ActivityLog::logCreate($adminUser, $agent, $agent->toArray());

            // Create referral code for this agent using system settings default values
            $systemSetting = \App\Models\SystemSetting::first();
            $referralCode = \App\Models\ReferralCode::create([
                'agent_id' => $agent->id,
                'code' => $systemSetting->referral_code_prefix.strtoupper(\Illuminate\Support\Str::random(8)),
                'is_active' => true,
                'commission_rate' => $systemSetting->commission_default_rate,
                'used_count' => 0,
                'expires_at' => now()->addYears(5),
            ]);

            // Log referral code creation
            ActivityLog::logCreate($adminUser, $referralCode, $referralCode->toArray());

            // Update agent with referral code
            $agent->update(['referral_code_id' => $referralCode->id]);

            // Log agent referral code assignment
            ActivityLog::logCustom($adminUser, 'referral_code_assigned', "Admin assigned referral code {$referralCode->code} to agent {$agent->id}", $agent);

            // Link user to agent
            $user->agents()->attach($agent->id);

            // Log user-agent relationship creation
            ActivityLog::logCustom($adminUser, 'user_agent_linked', "Admin linked user {$user->email} to agent {$agent->id}", $agent);

            DB::commit();

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
        $agent = Agent::with(['users', 'bankAccount', 'referralCode'])->findOrFail($id);

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
                'company_name' => $agent->company_name,
                'company_registration_number' => $agent->company_registration_number,
                'company_address' => $agent->company_address,
                'company_phone' => $agent->company_phone,
                'company_reg_file' => $agent->company_reg_file,
                'status' => $agent->status,
                'created_at' => $agent->created_at->format('Y-m-d H:i:s'),
                'user_email' => $agent->users->first()?->email,
                'bank_account' => $agent->bankAccount,
                'referral_code' => $agent->referralCode,
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
                'company_name' => $agent->company_name,
                'company_registration_number' => $agent->company_registration_number,
                'company_address' => $agent->company_address,
                'company_phone' => $agent->company_phone,
                'company_reg_file' => $agent->company_reg_file,
                'status' => $agent->status,
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
            'status' => 'required|in:active,inactive,suspended,banned',
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
            $rules['company_name'] = 'required|string|max:255';
            $rules['company_registration_number'] = 'required|string|max:255';
            $rules['company_address'] = 'required|string';
            $rules['company_phone'] = 'required|string|max:255';
            $rules['company_email_address'] = 'required_if:profile_type,company|nullable|email|max:255';
            $rules['company_reg_file'] = 'nullable|file|mimes:pdf,jpeg,jpg,png|max:10240';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            // Update agent
            $agentData = [
                'profile_type' => $request->profile_type,
                'status' => $request->status,
                'about' => $request->about,
            ];

            if ($request->profile_type === 'individual') {
                $agentData['individual_name'] = $request->individual_name;
                $agentData['individual_phone'] = $request->individual_phone;
                $agentData['individual_email'] = $request->individual_email;
                $agentData['individual_address'] = $request->individual_address;
                $agentData['individual_id_number'] = $request->individual_id_number;
                // Clear company fields and delete old file if exists
                $agentData['company_representative_name'] = null;
                $agentData['company_name'] = null;
                $agentData['company_registration_number'] = null;
                $agentData['company_address'] = null;
                $agentData['company_phone'] = null;
                $agentData['company_email_address'] = null;
                if ($agent->company_reg_file && Storage::disk('local')->exists($agent->company_reg_file)) {
                    Storage::disk('local')->delete($agent->company_reg_file);
                }
                $agentData['company_reg_file'] = null;
            } else {
                $agentData['company_representative_name'] = $request->company_representative_name;
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

        $allowedFields = ['individual_id_file', 'company_reg_file'];
        if (! in_array($field, $allowedFields)) {
            abort(404);
        }

        $filePath = $agent->$field;
        if (! $filePath || ! Storage::disk('local')->exists($filePath)) {
            abort(404);
        }

        return response()->download(Storage::disk('local')->path($filePath));
    }

    /**
     * Approve agent application
     */
    public function approve($id)
    {
        $adminUser = Auth::user();
        $agent = Agent::findOrFail($id);

        DB::beginTransaction();
        try {
            // Capture before data for activity logging
            $beforeData = $agent->toArray();

            // Update agent status to active
            $agent->update(['status' => 'active']);

            // Capture after data for activity logging
            $afterData = $agent->toArray();

            // Log the agent approval activity
            ActivityLog::logUpdate($adminUser, $agent, $beforeData, $afterData);
            ActivityLog::logCustom($adminUser, 'agent_approved', "Admin approved agent application for agent {$agent->id}", $agent);

            DB::commit();

            return redirect()->route('admin.agents.list')->with('success', 'Agent approved successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Failed to approve agent. '.$e->getMessage()]);
        }
    }

    /**
     * Export agents data to Excel
     */
    public function export()
    {
        return Excel::download(new AgentsExport, 'agents.xls');
    }
}
