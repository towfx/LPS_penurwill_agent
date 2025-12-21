<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Commission;
use App\Models\Payout;
use App\Models\PayoutItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class RequestPayoutController extends Controller
{
    /**
     * Display the request payout page
     */
    public function index(Request $request)
    {
        $agent = auth()->user()->agents()->first();

        if (! $agent) {
            return redirect()->route('agent.dashboard');
        }

        // Get filter parameters
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        // Build query for pending commissions
        $query = Commission::with(['sale'])
            ->where('agent_id', $agent->id)
            ->where('status', 'pending')
            // Exclude commissions already in a payout
            ->whereDoesntHave('payoutItems');

        // Apply date range filter based on sale_date
        if ($startDate && $endDate) {
            $query->whereHas('sale', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('sale_date', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay(),
                ]);
            });
        }

        // Order by sale_date descending
        $commissions = $query->get()->sortByDesc(function ($commission) {
            return $commission->sale?->sale_date;
        })->values();

        // Format commissions data for frontend
        $commissionsData = $commissions->map(function ($commission) {
            return [
                'id' => $commission->id,
                'sale_date' => $commission->sale?->sale_date?->toIso8601String(),
                'description' => $commission->sale?->description,
                'invoice_number' => $commission->sale?->invoice_number,
                'amount' => $commission->sale?->amount,
                'commission_amount' => $commission->amount,
            ];
        });

        return Inertia::render('Agent/RequestPayout', [
            'commissions' => $commissionsData,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'agent' => $agent,
        ]);
    }

    /**
     * Store a new payout request
     */
    public function store(Request $request)
    {
        $request->validate([
            'commissions' => 'required|array|min:1',
            'commissions.*' => 'required|exists:commissions,id',
        ]);

        $agent = auth()->user()->agents()->first();

        if (! $agent) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Failed, please contact support.',
            ], 403);
        }

        $user = auth()->user();

        try {
            return DB::transaction(function () use ($request, $agent, $user) {
                // Get all requested commissions
                $commissions = Commission::whereIn('id', $request->commissions)
                    ->where('agent_id', $agent->id)
                    ->where('status', 'pending')
                    ->whereDoesntHave('payoutItems')
                    ->get();

                // Verify all commissions are valid
                if ($commissions->count() !== count($request->commissions)) {
                    return response()->json([
                        'status' => 'failed',
                        'message' => 'Some commissions are invalid or already included in a payout. Please refresh and try again.',
                    ], 422);
                }

                // Calculate total amount
                $totalAmount = $commissions->sum('amount');

                // Create payout
                $payout = Payout::create([
                    'agent_id' => $agent->id,
                    'amount' => $totalAmount,
                    'status' => 'pending',
                    'created_by' => $user->id,
                ]);

                // Create payout items
                foreach ($commissions as $commission) {
                    PayoutItem::create([
                        'payout_id' => $payout->id,
                        'commission_id' => $commission->id,
                        'amount' => $commission->amount,
                    ]);
                }

                // Log activity
                ActivityLog::logCreate($user, $payout, $payout->toArray());

                // Send email notification to Partner
                try {
                    $partner = \App\Models\Partner::find(1);
                    if ($partner && $partner->company_email) {
                        \Illuminate\Support\Facades\Mail::to($partner->company_email)
                            ->send(new \App\Mail\PayoutRequestNotification($payout));
                    } else {
                        \Illuminate\Support\Facades\Log::warning('Partner with ID 1 not found or has no email address', [
                            'payout_id' => $payout->id,
                        ]);
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Failed to send payout request notification email', [
                        'payout_id' => $payout->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                // Format amount for message
                $formattedAmount = number_format($totalAmount, 2);
                $agentName = $agent->name;
                $partnerId = $agent->partner_id ?? 'N/A';

                return response()->json([
                    'status' => 'OK',
                    'message' => "RM {$formattedAmount} payout for {$agentName} created for processing by [Partner id:{$partnerId}]",
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Failed, please contact support.',
            ], 500);
        }
    }
}
