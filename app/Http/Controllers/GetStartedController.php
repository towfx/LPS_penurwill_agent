<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class GetStartedController extends Controller
{
    /**
     * Pre-check an email address to determine the appropriate flow.
     * Returns: {status: 'new'|'login'|'reset'|'no_password'}
     */
    public function checkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json(['status' => 'new']);
        }

        if (! $user->password) {
            return response()->json(['status' => 'no_password']);
        }

        if (! $user->email_verified_at) {
            return response()->json(['status' => 'reset']);
        }

        return response()->json(['status' => 'login']);
    }
}
