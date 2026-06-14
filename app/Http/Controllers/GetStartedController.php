<?php

namespace App\Http\Controllers;

use App\Models\Agent;
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

        $email = strtolower(trim($request->email));
        $user = User::where('email', $email)->first();

        if (! $user) {
            $agentExists = Agent::where('individual_email', $email)->exists()
                || Agent::where('company_email_address', $email)->exists();

            if ($agentExists) {
                return response()->json(['status' => 'login']);
            }

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
