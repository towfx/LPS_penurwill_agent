<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user();

        return Inertia::render('Profile/Show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at->format('Y-m-d H:i:s'),
            ]
        ]);
    }

    public function edit()
    {
        $user = Auth::user();

        return Inertia::render('Profile/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'current_password' => 'nullable|string',
            'password' => 'nullable|string|min:12|confirmed',
        ]);

        // Update name
        $user->update([
            'name' => $data['name']
        ]);

        // Update password if provided
        if ($request->filled('password')) {
            // Validate current password
            if (!Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => 'The current password is incorrect.']);
            }

            // Validate password format (frontend validation should handle this, but backend validation as backup)
            if (!preg_match('/^(?=.*[0-9])(?=.*[!@#$%^&*()_+\-=\[\]{}|;:,.<>?])[A-Za-z0-9!@#$%^&*()_+\-=\[\]{}|;:,.<>?]{12,}$/', $request->password)) {
                return back()->withErrors(['password' => 'Password must be at least 12 characters with numbers and special characters.']);
            }

            $user->update([
                'password' => Hash::make($request->password)
            ]);
        }

        return redirect()->route('profile.show')->with('success', 'Profile updated successfully!');
    }
}
