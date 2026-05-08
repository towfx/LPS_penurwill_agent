<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\PayoutCancelledNotification;
use App\Models\ActivityLog;
use App\Models\Agent;
use App\Models\AgentNotification;
use App\Models\Commission;
use App\Models\Payout;
use App\Models\PayoutItem;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class PayoutController extends Controller
{
    /**
     * Display the payouts list page
     */
    public function index(Request $request)
    {
        // Get all payouts with agent relationship
        $payouts = Payout::with('agent')
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
                'agent_name' => $payout->agent->name ?? 'Unknown',
                'amount' => $payout->amount,
                'status' => $payout->status,
                'created_at' => $payout->created_at?->toIso8601String(),
                'paid_at' => $payout->paid_at?->toIso8601String(),
                'items_count' => $payout->payout_items_count,
            ];
        });

        return Inertia::render('Admin/PayoutsList', [
            'payouts' => $payoutsData,
            'summary' => [
                'total_payouts' => $totalPayouts,
                'total_amount' => $totalAmount,
                'status_breakdown' => $statusBreakdown,
            ],
        ]);
    }

    /**
     * Display the payout detail page
     */
    public function show(Request $request, $id, \App\Services\PayoutReportGenerator $reportGenerator)
    {
        $payout = Payout::with(['agent.bankAccount', 'payoutItems.commission.sale'])
            ->findOrFail($id);

        $year = (int) $payout->created_at->format('Y');
        $month = (int) $payout->created_at->format('n');

        // Get months for display
        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
        ];

        return Inertia::render('Admin/PayoutDetail', [
            'payout' => $payout,
            'agent' => $payout->agent,
            'year' => $year,
            'month' => $month,
            'monthName' => $months[$month] ?? 'Unknown',
            'breakdown' => [
                'by_commission_type' => $reportGenerator->byCommissionType($payout->agent, $year, $month),
                'by_sales_source' => $reportGenerator->bySalesSource($payout->agent, $year, $month),
                'transactions' => $reportGenerator->transactions($payout->agent, $year, $month),
            ],
        ]);
    }

    /**
     * Upload bank transfer file
     */
    public function uploadBankTransfer(Request $request, $id)
    {
        try {
            $request->validate([
                'bank_transfer_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB max
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'fail',
                'message' => $e->getMessage(),
            ], 422);
        }

        $payout = Payout::findOrFail($id);
        $user = auth()->user();

        try {
            // Ensure payouts directory exists
            $directory = storage_path('app/payouts');
            if (! is_dir($directory)) {
                if (! mkdir($directory, 0755, true)) {
                    return response()->json([
                        'status' => 'fail',
                        'message' => 'Failed to create upload directory. Please contact support.',
                    ], 500);
                }
            }

            // Verify directory was created or exists
            if (! is_dir($directory)) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Upload directory does not exist and could not be created.',
                ], 500);
            }

            // Store file
            $file = $request->file('bank_transfer_file');
            if (! $file || ! $file->isValid()) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Invalid file uploaded.',
                ], 400);
            }

            $fileName = 'payout_'.$payout->id.'_'.time().'.'.$file->getClientOriginalExtension();
            $filePath = $directory.DIRECTORY_SEPARATOR.$fileName;

            // Move file and check if it succeeded
            if (! $file->move($directory, $fileName)) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Failed to save file. Please try again.',
                ], 500);
            }

            // Verify file exists after moving
            if (! file_exists($filePath)) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'File was not saved correctly. Please try again.',
                ], 500);
            }

            // Update payout
            $before = $payout->toArray();
            $payout->update([
                'bank_transfer_file' => $fileName,
            ]);

            // Log activity
            if ($user) {
                ActivityLog::logUpdate($user, $payout, $before, $payout->toArray());
            }

            return response()->json([
                'status' => 'OK',
                'message' => 'Bank transfer file uploaded successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'fail',
                'message' => 'An error occurred while uploading the file: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark payout as paid
     */
    public function markAsPaid(Request $request, $id)
    {
        $payout = Payout::findOrFail($id);
        $user = auth()->user();

        $before = $payout->toArray();

        $payout->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        // Log activity
        if ($user) {
            ActivityLog::logUpdate($user, $payout, $before, $payout->toArray());
        }

        // Send CommissionPaidNotification per commission earner
        try {
            $payout->load('payoutItems.commission.earningAgent.users');
            foreach ($payout->payoutItems as $item) {
                $commission = $item->commission;
                if (! $commission) {
                    continue;
                }
                $earner = $commission->earningAgent;
                $earnerEmail = $earner?->users?->first()?->email;
                if ($earnerEmail) {
                    \Illuminate\Support\Facades\Mail::to($earnerEmail)
                        ->send(new \App\Mail\CommissionPaidNotification($commission));
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send CommissionPaidNotification', [
                'payout_id' => $payout->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Send email notification to Agent
        try {
            $agent = $payout->agent;
            $agentUser = $agent->users()->first();

            if ($agentUser && $agentUser->email) {
                $ccEmails = [];

                // Determine CC email based on agent profile type
                if ($agent->profile_type === 'individual' && $agent->individual_email) {
                    $ccEmails[] = $agent->individual_email;
                } elseif ($agent->profile_type === 'company' && $agent->company_email_address) {
                    $ccEmails[] = $agent->company_email_address;
                }

                $mail = \Illuminate\Support\Facades\Mail::to($agentUser->email);

                if (! empty($ccEmails)) {
                    $mail->cc($ccEmails);
                }

                $mail->send(new \App\Mail\PayoutPaidNotification($payout));
            } else {
                \Illuminate\Support\Facades\Log::warning('Agent user email not found for payout notification', [
                    'payout_id' => $payout->id,
                    'agent_id' => $agent->id,
                ]);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send payout paid notification email', [
                'payout_id' => $payout->id,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()->back()->with('success', 'Payout marked as paid successfully.');
    }

    /**
     * Download bank transfer file
     */
    public function downloadBankTransfer(Request $request, $id)
    {
        $payout = Payout::findOrFail($id);

        if (! $payout->bank_transfer_file) {
            abort(404, 'Bank transfer file not found');
        }

        $filePath = storage_path('app/payouts/'.$payout->bank_transfer_file);

        if (! file_exists($filePath)) {
            abort(404, 'File not found');
        }

        return response()->download($filePath);
    }

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
            DB::raw('COUNT(*) as total_sales'),
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
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
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

        // Block if any selected commission is cancelled
        $cancelledCount = Commission::whereIn('id', $request->commission_ids)
            ->where('status', Commission::STATUS_CANCELLED)
            ->count();
        if ($cancelledCount > 0) {
            return back()->withErrors(['error' => "Cannot create payout: {$cancelledCount} selected commission(s) are cancelled."])->withInput();
        }

        return DB::transaction(function () use ($request, $user) {
            // Update commission approval statuses
            $allCommissions = Commission::where('earning_agent_id', $request->agent_id)
                ->whereYear('created_at', $request->year)
                ->whereMonth('created_at', $request->month)
                ->where('status', '!=', Commission::STATUS_CANCELLED)
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

            // Create payout items, copying commission_type / commission_category for breakdown reports
            foreach ($request->commission_ids as $commissionId) {
                $commission = Commission::find($commissionId);
                if ($commission && $commission->status === 'approved') {
                    PayoutItem::create([
                        'payout_id' => $payout->id,
                        'commission_id' => $commissionId,
                        'amount' => $commission->amount,
                        'commission_type' => $commission->commission_type,
                        'commission_category' => $commission->commission_category,
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
            DB::raw('COUNT(*) as total_sales'),
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
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
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
                        'commission_type' => $commission->commission_type,
                        'commission_category' => $commission->commission_category,
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

    /**
     * Cancel a payout. Admin note is required.
     */
    public function cancel(Request $request, $id, NotificationService $notificationService)
    {
        $request->validate([
            'admin_note' => 'required|string|max:1000',
        ]);

        $user = auth()->user();
        $payout = Payout::with('agent')->findOrFail($id);

        if ($payout->status === 'paid') {
            return back()->withErrors(['error' => 'Cannot cancel a payout that has already been paid.']);
        }

        $before = $payout->toArray();
        $payout->update([
            'status' => 'cancelled',
            'admin_note' => $request->admin_note,
        ]);
        ActivityLog::logUpdate($user, $payout, $before, $payout->toArray());
        ActivityLog::logCustom($user, 'payout_cancelled', "Admin cancelled payout #{$payout->id}: {$request->admin_note}", $payout);

        // Notify agent
        if ($payout->agent) {
            $notificationService->notify(
                $payout->agent,
                AgentNotification::TYPE_PAYOUT_CANCELLED,
                'Payout Cancelled',
                "Your payout of {$payout->amount} has been cancelled. Reason: {$request->admin_note}",
                Payout::class,
                $payout->id,
            );

            try {
                $agentUser = $payout->agent->users()->first();
                if ($agentUser?->email) {
                    Mail::to($agentUser->email)->send(new PayoutCancelledNotification($payout));
                }
            } catch (\Throwable $e) {
                Log::warning('PayoutController: cancel email failed', ['payout_id' => $payout->id, 'error' => $e->getMessage()]);
            }
        }

        return redirect()->route('admin.payouts.list')->with('success', 'Payout cancelled.');
    }
}
