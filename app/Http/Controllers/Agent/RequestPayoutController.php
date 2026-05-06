<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Agent;
use App\Models\Commission;
use App\Models\Payout;
use App\Models\PayoutItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class RequestPayoutController extends Controller
{
    /**
     * Display the request payout page.
     */
    public function index(Request $request)
    {
        $agent = auth()->user()->agents()->first();

        if (! $agent) {
            return redirect()->route('agent.dashboard');
        }

        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        // Pending earned commissions not yet in any payout (Decision 19: include reversals)
        $query = Commission::with(['sale'])
            ->where('earning_agent_id', $agent->id)
            ->where('status', Commission::STATUS_PENDING)
            ->whereDoesntHave('payoutItems');

        if ($startDate && $endDate) {
            $query->whereHas('sale', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('sale_date', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay(),
                ]);
            });
        }

        $commissions = $query->get()->sortByDesc(fn ($c) => $c->sale?->sale_date)->values();

        $commissionsData = $commissions->map(function ($commission) {
            return [
                'id' => $commission->id,
                'sale_date' => $commission->sale?->sale_date?->toIso8601String(),
                'description' => $commission->sale?->description,
                'invoice_number' => $commission->sale?->invoice_number,
                'amount' => $commission->sale?->amount,
                'commission_amount' => $commission->amount,
                'commission_type' => $commission->commission_type,
                'commission_category' => $commission->commission_category,
                'is_reversal' => (bool) $commission->is_reversal,
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
     * Store a new payout request — automatically includes pending reversals
     * (Decision 19); blocks if net total ≤ 0.
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
                // Selected commissions
                $selected = Commission::whereIn('id', $request->commissions)
                    ->where('earning_agent_id', $agent->id)
                    ->where('status', Commission::STATUS_PENDING)
                    ->whereDoesntHave('payoutItems')
                    ->get();

                if ($selected->count() !== count($request->commissions)) {
                    return response()->json([
                        'status' => 'failed',
                        'message' => 'Some commissions are invalid or already included in a payout. Please refresh and try again.',
                    ], 422);
                }

                // Decision 19: include any pending reversals not already in selection
                $pendingReversals = Commission::where('earning_agent_id', $agent->id)
                    ->where('status', Commission::STATUS_PENDING)
                    ->where('is_reversal', true)
                    ->whereDoesntHave('payoutItems')
                    ->whereNotIn('id', $selected->pluck('id'))
                    ->get();

                $allCommissions = $selected->merge($pendingReversals);
                $netTotal = (float) $allCommissions->sum('amount');

                if ($netTotal <= 0) {
                    return response()->json([
                        'status' => 'failed',
                        'message' => 'Net payout amount is zero or negative due to pending reversals.',
                    ], 422);
                }

                $payout = Payout::create([
                    'agent_id' => $agent->id,
                    'amount' => $netTotal,
                    'status' => 'pending',
                    'created_by' => $user->id,
                ]);

                foreach ($allCommissions as $commission) {
                    PayoutItem::create([
                        'payout_id' => $payout->id,
                        'commission_id' => $commission->id,
                        'amount' => $commission->amount,
                        'commission_type' => $commission->commission_type,
                        'commission_category' => $commission->commission_category,
                    ]);
                }

                ActivityLog::logCreate($user, $payout, $payout->toArray());

                // Notify the seeded business-partner agent (QNA-03)
                try {
                    $bpAgent = Agent::query()
                        ->where('agent_role', Agent::ROLE_BUSINESS_PARTNER)
                        ->orderBy('id')
                        ->first();
                    $bpEmail = $bpAgent?->company_email_address ?: $bpAgent?->users()->first()?->email;
                    if ($bpEmail) {
                        Mail::to($bpEmail)
                            ->send(new \App\Mail\PayoutRequestNotification($payout));
                    } else {
                        Log::warning('No business-partner agent email found for payout request notification', [
                            'payout_id' => $payout->id,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to send payout request notification email', [
                        'payout_id' => $payout->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                $formattedAmount = number_format($netTotal, 2);
                $agentName = $agent->name;

                return response()->json([
                    'status' => 'OK',
                    'message' => "RM {$formattedAmount} payout for {$agentName} created for processing.",
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
