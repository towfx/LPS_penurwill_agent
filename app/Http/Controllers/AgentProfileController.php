<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
            'individual_id_number' => 'nullable|string|max:255',
            'individual_id_file' => 'nullable|file|mimes:pdf,jpeg,jpg,png|max:10240',
            'company_representative_name' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'company_registration_number' => 'nullable|string|max:255',
            'company_address' => 'nullable|string',
            'company_phone' => 'nullable|string|max:255',
            'company_email_address' => 'nullable|email|max:255',
            'company_reg_file' => 'nullable|file|mimes:pdf,jpeg,jpg,png|max:10240',
            'about' => 'nullable|string|max:1000',
            // Bank account fields
            'bank_account_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'iban' => 'nullable|string|max:255',
            'swift_code' => 'nullable|string|max:255',
        ]);

        if ($data['profile_type'] === 'individual') {
            // Validate required individual fields
            $request->validate([
                'individual_id_number' => 'required|string|max:255',
            ]);

            $data['company_representative_name'] = null;
            $data['company_name'] = null;
            $data['company_registration_number'] = null;
            $data['company_address'] = null;
            $data['company_phone'] = null;
            $data['company_email_address'] = null;
            $data['company_reg_file'] = null;
        } else {
            $data['individual_name'] = null;
            $data['individual_phone'] = null;
            $data['individual_address'] = null;
            $data['individual_id_number'] = null;
            $data['individual_id_file'] = null;
        }

        // Remove status and referral_code from data to prevent updates
        unset($data['status']);
        unset($data['referral_code']);

        // Remove file fields from data - we handle them separately to preserve existing files
        unset($data['individual_id_file']);
        unset($data['company_reg_file']);

        // Handle file uploads - only update if a new file is provided
        if ($data['profile_type'] === 'individual' && $request->hasFile('individual_id_file')) {
            // Delete old file if exists
            if ($agent->individual_id_file && Storage::disk('local')->exists($agent->individual_id_file)) {
                Storage::disk('local')->delete($agent->individual_id_file);
            }

            $file = $request->file('individual_id_file');
            $extension = $file->getClientOriginalExtension();
            $filename = Str::random(40).'.'.$extension;
            $path = "agents/{$agent->id}/{$filename}";

            Storage::disk('local')->put($path, file_get_contents($file));
            $data['individual_id_file'] = $path;
        } elseif ($data['profile_type'] === 'company' && $request->hasFile('company_reg_file')) {
            // Delete old file if exists
            if ($agent->company_reg_file && Storage::disk('local')->exists($agent->company_reg_file)) {
                Storage::disk('local')->delete($agent->company_reg_file);
            }

            $file = $request->file('company_reg_file');
            $extension = $file->getClientOriginalExtension();
            $filename = Str::random(40).'.'.$extension;
            $path = "agents/{$agent->id}/{$filename}";

            Storage::disk('local')->put($path, file_get_contents($file));
            $data['company_reg_file'] = $path;
        } else {
            // Preserve existing file paths when no new file is uploaded
            // Only preserve files for the current profile type (switching types already cleared opposite files above)
            if ($data['profile_type'] === 'individual' && $agent->individual_id_file) {
                // Keep existing individual_id_file if no new file uploaded and staying as individual
                $data['individual_id_file'] = $agent->individual_id_file;
            } elseif ($data['profile_type'] === 'company' && $agent->company_reg_file) {
                // Keep existing company_reg_file if no new file uploaded and staying as company
                $data['company_reg_file'] = $agent->company_reg_file;
            }
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

    public function downloadFile($field)
    {
        $user = Auth::user();
        $agent = $user->agents()->first();

        if (!$agent) {
            abort(404);
        }

        $allowedFields = ['individual_id_file', 'company_reg_file'];
        if (!in_array($field, $allowedFields)) {
            abort(404);
        }

        $filePath = $agent->$field;
        if (!$filePath || !Storage::disk('local')->exists($filePath)) {
            abort(404);
        }

        return response()->download(Storage::disk('local')->path($filePath));
    }
}
