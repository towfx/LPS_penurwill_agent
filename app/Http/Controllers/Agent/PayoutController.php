<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Payout;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PayoutController extends Controller
{
    /**
     * Display the payout detail page for agents (read-only)
     */
    public function show(Request $request, $id)
    {
        $agent = auth()->user()->agents()->first();

        if (!$agent) {
            return redirect()->route('agent.dashboard');
        }

        // Get payout with related data, ensuring it belongs to the current agent
        $payout = Payout::with(['agent', 'payoutItems.commission.sale'])
            ->where('id', $id)
            ->where('agent_id', $agent->id)
            ->firstOrFail();

        $year = $payout->created_at->format('Y');
        $month = $payout->created_at->format('n');

        // Get months for display
        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];

        return Inertia::render('Agent/PayoutDetail', [
            'payout' => $payout,
            'agent' => $payout->agent,
            'year' => (int) $year,
            'month' => (int) $month,
            'monthName' => $months[$month] ?? 'Unknown',
        ]);
    }
}
