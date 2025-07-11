<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AgentProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = Auth::user();
        $agent = $user->agents()->first();

        return Inertia::render('Agent/Profile', [
            'agent' => $agent
        ]);
    }

    public function edit(Request $request)
    {
        $user = Auth::user();
        $agent = $user->agents()->first();

        return Inertia::render('Agent/ProfileEdit', [
            'agent' => $agent
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $agent = $user->agents()->first();
        if (!$agent) {
            abort(404);
        }

        $data = $request->validate([
            'profile_type' => 'required|in:individual,company',
            'individual_name' => 'nullable|string|max:255',
            'individual_phone' => 'nullable|string|max:255',
            'individual_address' => 'nullable|string',
            'company_representative_name' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'company_registration_number' => 'nullable|string|max:255',
            'company_address' => 'nullable|string',
            'company_phone' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive,suspended,banned',
        ]);

        if ($data['profile_type'] === 'individual') {
            $data['company_representative_name'] = null;
            $data['company_name'] = null;
            $data['company_registration_number'] = null;
            $data['company_address'] = null;
            $data['company_phone'] = null;
        } else {
            $data['individual_name'] = null;
            $data['individual_phone'] = null;
            $data['individual_address'] = null;
        }

        $agent->update($data);

        return redirect()->route('agent.profile')->with('success', 'Profile updated successfully!');
    }
}
