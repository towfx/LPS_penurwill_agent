<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
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
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
            'terms' => 'required|accepted'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Create new user
        $user = User::create([
            'name' => $request->first_name . ' ' . $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Log the user in
        \Illuminate\Support\Facades\Auth::login($user);

        // Redirect to agent dashboard
        return redirect()->route('agent.dashboard')->with('success', 'Account created successfully! Welcome to Penurwill Agent System.');
    }
}
