<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Commission;
use App\Models\Payout;
use App\Models\PayoutItem;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PayoutController extends Controller
{
    /**
     * Display the payout creation page
     */
    public function create(Request $request)
    {
        $agentId = $request->get('agent_id');
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('n'));

        $agent = Agent::findOrFail($agentId);

        // Get commission summary
        $summary = Commission::select([
                DB::raw('SUM(amount) as total_commission'),
                DB::raw('COUNT(*) as total_sales')
            ])
            ->where('agent_id', $agentId)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->first();

        // Get commissions for this month
        $commissions = Commission::where('agent_id', $agentId)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->with(['sale'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Get months for display
        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];

        return Inertia::render('Admin/PayoutCreate', [
            'agent' => $agent,
            'summary' => $summary,
            'commissions' => $commissions,
            'year' => (int) $year,
            'month' => (int) $month,
            'monthName' => $months[$month] ?? 'Unknown',
        ]);
    }

    /**
     * Store a new payout
     */
    public function store(Request $request)
    {
        $request->validate([
            'agent_id' => 'required|exists:agents,id',
            'year' => 'required|integer',
            'month' => 'required|integer|between:1,12',
            'amount' => 'required|numeric|min:0',
            'is_paid' => 'boolean',
            'paid_at' => 'nullable|date|required_if:is_paid,true',
            'commission_ids' => 'required|array|min:1',
            'commission_ids.*' => 'exists:commissions,id',
        ]);

                $user = auth()->user();

        return DB::transaction(function () use ($request, $user) {
            // Update commission approval statuses
            $allCommissions = Commission::where('agent_id', $request->agent_id)
                ->whereYear('created_at', $request->year)
                ->whereMonth('created_at', $request->month)
                ->get();

            foreach ($allCommissions as $commission) {
                $isApproved = in_array($commission->id, $request->commission_ids);
                $newStatus = $isApproved ? 'approved' : 'pending';

                if ($commission->status !== $newStatus) {
                    $before = $commission->toArray();
                    $commission->update(['status' => $newStatus]);
                    if ($user) {
                        ActivityLog::logUpdate($user, $commission, $before, $commission->toArray());
                    }
                }
            }

            // Create payout
            $payout = Payout::create([
                'agent_id' => $request->agent_id,
                'amount' => $request->amount,
                'status' => $request->is_paid ? 'paid' : 'pending',
                'paid_at' => $request->is_paid ? $request->paid_at : null,
                'created_by' => $user ? $user->id : null,
            ]);

            // Create payout items only for selected commissions
            foreach ($request->commission_ids as $commissionId) {
                $commission = Commission::find($commissionId);
                if ($commission && $commission->status === 'approved') {
                    PayoutItem::create([
                        'payout_id' => $payout->id,
                        'commission_id' => $commissionId,
                        'amount' => $commission->amount,
                    ]);
                }
            }

            // Log activity
            if ($user) {
                ActivityLog::logCreate($user, $payout, $payout->toArray());
            }

            return redirect()->route('admin.commissions.list')
                ->with('success', 'Payout created successfully.');
        });
    }

    /**
     * Display the payout update page
     */
    public function edit(Request $request, $id)
    {
        $payout = Payout::with(['agent', 'payoutItems.commission.sale'])->findOrFail($id);

        $year = $payout->created_at->format('Y');
        $month = $payout->created_at->format('n');

        // Get commission summary
        $summary = Commission::select([
                DB::raw('SUM(amount) as total_commission'),
                DB::raw('COUNT(*) as total_sales')
            ])
            ->where('agent_id', $payout->agent_id)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->first();

        // Get all commissions for this month
        $commissions = Commission::where('agent_id', $payout->agent_id)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->with(['sale'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Mark which commissions are already in payout
        $commissions->each(function ($commission) use ($payout) {
            $commission->is_in_payout = $payout->payoutItems->contains('commission_id', $commission->id);
        });

        // Get months for display
        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];

        return Inertia::render('Admin/PayoutUpdate', [
            'payout' => $payout,
            'agent' => $payout->agent,
            'summary' => $summary,
            'commissions' => $commissions,
            'year' => (int) $year,
            'month' => (int) $month,
            'monthName' => $months[$month] ?? 'Unknown',
        ]);
    }

    /**
     * Update a payout
     */
    public function update(Request $request, $id)
    {
        $payout = Payout::findOrFail($id);

        $request->validate([
            'amount' => 'required|numeric|min:0',
            'is_paid' => 'boolean',
            'paid_at' => 'nullable|date|required_if:is_paid,true',
            'commission_ids' => 'required|array|min:1',
            'commission_ids.*' => 'exists:commissions,id',
        ]);

        $user = auth()->user();

        return DB::transaction(function () use ($request, $payout, $user) {
            $before = $payout->toArray();

            // Update commission approval statuses
            $allCommissions = Commission::where('agent_id', $payout->agent_id)
                ->whereYear('created_at', $payout->created_at->format('Y'))
                ->whereMonth('created_at', $payout->created_at->format('n'))
                ->get();

            foreach ($allCommissions as $commission) {
                $isApproved = in_array($commission->id, $request->commission_ids);
                $newStatus = $isApproved ? 'approved' : 'pending';

                if ($commission->status !== $newStatus) {
                    $commissionBefore = $commission->toArray();
                    $commission->update(['status' => $newStatus]);
                    if ($user) {
                        ActivityLog::logUpdate($user, $commission, $commissionBefore, $commission->toArray());
                    }
                }
            }

            // Update payout
            $payout->update([
                'amount' => $request->amount,
                'status' => $request->is_paid ? 'paid' : 'pending',
                'paid_at' => $request->is_paid ? $request->paid_at : null,
            ]);

            // Remove existing payout items
            $payout->payoutItems()->delete();

            // Create new payout items only for selected approved commissions
            foreach ($request->commission_ids as $commissionId) {
                $commission = Commission::find($commissionId);
                if ($commission && $commission->status === 'approved') {
                    PayoutItem::create([
                        'payout_id' => $payout->id,
                        'commission_id' => $commissionId,
                        'amount' => $commission->amount,
                    ]);
                }
            }

            // Log activity
            if ($user) {
                ActivityLog::logUpdate($user, $payout, $before, $payout->toArray());
            }

            return redirect()->route('admin.commissions.list')
                ->with('success', 'Payout updated successfully.');
        });
    }
}
