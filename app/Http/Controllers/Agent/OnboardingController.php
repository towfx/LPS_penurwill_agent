<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OnboardingController extends Controller
{
    public function show(Request $request)
    {
        $agent = auth()->user()->agents()->first();

        return Inertia::render('GetStartedGuide', [
            'agentRole' => $agent?->agent_role ?? 'agent',
        ]);
    }

    public function complete(Request $request)
    {
        $agent = auth()->user()->agents()->first();
        if ($agent && $agent->first_login_at === null) {
            $agent->update(['first_login_at' => now()]);
        }

        return redirect()->route('agent.dashboard');
    }
}
