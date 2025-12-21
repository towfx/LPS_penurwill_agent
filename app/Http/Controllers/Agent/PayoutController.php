<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Payout;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PayoutController extends Controller
{
    /**
     * Display the payouts list page
     */
    public function index(Request $request)
    {
        $agent = auth()->user()->agents()->first();

        if (! $agent) {
            return redirect()->route('agent.dashboard');
        }

        // Get all payouts for the agent
        $payouts = Payout::where('agent_id', $agent->id)
            ->withCount('payoutItems')
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate summary statistics
        $totalPayouts = $payouts->count();
        $totalAmount = $payouts->sum('amount');
        $statusBreakdown = [
            'pending' => $payouts->where('status', 'pending')->count(),
            'approved' => $payouts->where('status', 'approved')->count(),
            'paid' => $payouts->where('status', 'paid')->count(),
        ];

        // Format payouts data for frontend
        $payoutsData = $payouts->map(function ($payout) {
            return [
                'id' => $payout->id,
                'amount' => $payout->amount,
                'status' => $payout->status,
                'created_at' => $payout->created_at?->toIso8601String(),
                'paid_at' => $payout->paid_at?->toIso8601String(),
                'items_count' => $payout->payout_items_count,
                'bank_transfer_file' => $payout->bank_transfer_file,
            ];
        });

        return Inertia::render('Agent/PayoutsList', [
            'payouts' => $payoutsData,
            'summary' => [
                'total_payouts' => $totalPayouts,
                'total_amount' => $totalAmount,
                'status_breakdown' => $statusBreakdown,
            ],
            'agent' => $agent,
        ]);
    }

    /**
     * Display the payout detail page for agents (read-only)
     */
    public function show(Request $request, $id)
    {
        $agent = auth()->user()->agents()->first();

        if (! $agent) {
            return redirect()->route('agent.dashboard');
        }

        // Get payout with related data, ensuring it belongs to the current agent
        $payout = Payout::with(['agent.bankAccount', 'payoutItems.commission.sale'])
            ->where('id', $id)
            ->where('agent_id', $agent->id)
            ->firstOrFail();

        $year = $payout->created_at->format('Y');
        $month = $payout->created_at->format('n');

        // Get months for display
        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
        ];

        return Inertia::render('Agent/PayoutDetail', [
            'payout' => $payout,
            'agent' => $payout->agent,
            'year' => (int) $year,
            'month' => (int) $month,
            'monthName' => $months[$month] ?? 'Unknown',
        ]);
    }

    /**
     * Download bank transfer file
     */
    public function downloadBankTransfer(Request $request, $id)
    {
        $agent = auth()->user()->agents()->first();

        if (! $agent) {
            abort(403);
        }

        $payout = Payout::where('id', $id)
            ->where('agent_id', $agent->id)
            ->firstOrFail();

        if (! $payout->bank_transfer_file) {
            abort(404, 'Bank transfer file not found');
        }

        $filePath = storage_path('app/payouts/'.$payout->bank_transfer_file);

        if (! file_exists($filePath)) {
            abort(404, 'File not found');
        }

        return response()->download($filePath);
    }
}
