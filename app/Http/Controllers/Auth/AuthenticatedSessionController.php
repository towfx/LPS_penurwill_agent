<?php

namespace App\Http\Controllers\Auth;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController as FortifyAuthenticatedSessionController;

class AuthenticatedSessionController extends FortifyAuthenticatedSessionController
{
    /**
     * Where to redirect users after login.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function authenticated(Request $request, $user)
    {
        // Log successful login
        if ($user instanceof User) {
            ActivityLog::logCustom($user, 'login', 'User logged in successfully', $user);
        }

        if ($user->hasRole('admin')) {
            return redirect('/admin/dashboard');
        }
        if ($user->hasRole('partner')) {
            return redirect('/partner/dashboard');
        }
        if ($user->hasRole('agent')) {
            return redirect('/agent/dashboard');
        }

        return redirect()->intended('/dashboard');
    }
}
