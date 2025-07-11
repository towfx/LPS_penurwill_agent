<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class AgentController extends Controller
{
    /**
     * Store a newly created agent
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'profile_type' => 'required|in:individual,company',
            'individual_name' => 'required_if:profile_type,individual|nullable|string|max:255',
            'individual_phone' => 'required_if:profile_type,individual|nullable|string|max:255',
            'individual_address' => 'required_if:profile_type,individual|nullable|string',
            'company_representative_name' => 'required_if:profile_type,company|nullable|string|max:255',
            'company_name' => 'required_if:profile_type,company|nullable|string|max:255',
            'company_registration_number' => 'required_if:profile_type,company|nullable|string|max:255',
            'company_address' => 'required_if:profile_type,company|nullable|string',
            'company_phone' => 'required_if:profile_type,company|nullable|string|max:255',
            'user_email' => 'required|email|unique:users,email',
            'user_password' => 'required|string|min:8|confirmed',
            'status' => 'required|in:active,inactive,suspended,banned',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Create user
        $user = User::create([
            'name' => $request->profile_type === 'individual'
                ? $request->individual_name
                : $request->company_representative_name,
            'email' => $request->user_email,
            'password' => Hash::make($request->user_password),
            'email_verified_at' => now(),
        ]);

        // Create agent
        $agentData = [
            'profile_type' => $request->profile_type,
            'status' => $request->status,
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

        // Link user to agent
        $user->agents()->attach($agent->id);

        return redirect()->route('admin.agents.list')->with('success', 'Agent created successfully!');
    }

    /**
     * Display the specified agent
     */
    public function show($id)
    {
        $agent = Agent::with('users')->findOrFail($id);

        return Inertia::render('Admin/AgentView', [
            'agent' => [
                'id' => $agent->id,
                'profile_type' => $agent->profile_type,
                'individual_name' => $agent->individual_name,
                'individual_phone' => $agent->individual_phone,
                'individual_address' => $agent->individual_address,
                'company_representative_name' => $agent->company_representative_name,
                'company_name' => $agent->company_name,
                'company_registration_number' => $agent->company_registration_number,
                'company_address' => $agent->company_address,
                'company_phone' => $agent->company_phone,
                'status' => $agent->status,
                'created_at' => $agent->created_at->format('Y-m-d H:i:s'),
                'user_email' => $agent->users->first()?->email,
            ]
        ]);
    }

    /**
     * Show the form for editing the specified agent
     */
    public function edit($id)
    {
        $agent = Agent::with('users')->findOrFail($id);

        return Inertia::render('Admin/AgentUpdate', [
            'id' => $id,
            'agent' => [
                'id' => $agent->id,
                'profile_type' => $agent->profile_type,
                'individual_name' => $agent->individual_name,
                'individual_phone' => $agent->individual_phone,
                'individual_address' => $agent->individual_address,
                'company_representative_name' => $agent->company_representative_name,
                'company_name' => $agent->company_name,
                'company_registration_number' => $agent->company_registration_number,
                'company_address' => $agent->company_address,
                'company_phone' => $agent->company_phone,
                'status' => $agent->status,
                'user_email' => $agent->users->first()?->email,
            ]
        ]);
    }

    /**
     * Update the specified agent
     */
    public function update(Request $request, $id)
    {
        $agent = Agent::findOrFail($id);
        $user = $agent->users->first();

        $validator = Validator::make($request->all(), [
            'profile_type' => 'required|in:individual,company',
            'individual_name' => 'required_if:profile_type,individual|nullable|string|max:255',
            'individual_phone' => 'required_if:profile_type,individual|nullable|string|max:255',
            'individual_address' => 'required_if:profile_type,individual|nullable|string',
            'company_representative_name' => 'required_if:profile_type,company|nullable|string|max:255',
            'company_name' => 'required_if:profile_type,company|nullable|string|max:255',
            'company_registration_number' => 'required_if:profile_type,company|nullable|string|max:255',
            'company_address' => 'required_if:profile_type,company|nullable|string',
            'company_phone' => 'required_if:profile_type,company|nullable|string|max:255',
            'user_password' => 'nullable|string|min:8|confirmed',
            'status' => 'required|in:active,inactive,suspended,banned',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Update agent
        $agentData = [
            'profile_type' => $request->profile_type,
            'status' => $request->status,
        ];

        if ($request->profile_type === 'individual') {
            $agentData['individual_name'] = $request->individual_name;
            $agentData['individual_phone'] = $request->individual_phone;
            $agentData['individual_address'] = $request->individual_address;
            // Clear company fields
            $agentData['company_representative_name'] = null;
            $agentData['company_name'] = null;
            $agentData['company_registration_number'] = null;
            $agentData['company_address'] = null;
            $agentData['company_phone'] = null;
        } else {
            $agentData['company_representative_name'] = $request->company_representative_name;
            $agentData['company_name'] = $request->company_name;
            $agentData['company_registration_number'] = $request->company_registration_number;
            $agentData['company_address'] = $request->company_address;
            $agentData['company_phone'] = $request->company_phone;
            // Clear individual fields
            $agentData['individual_name'] = null;
            $agentData['individual_phone'] = null;
            $agentData['individual_address'] = null;
        }

        $agent->update($agentData);

        // Update user if password is provided
        if ($request->filled('user_password')) {
            $user->update([
                'password' => Hash::make($request->user_password),
            ]);
        }

        return redirect()->route('admin.agents.list')->with('success', 'Agent updated successfully!');
    }
}
