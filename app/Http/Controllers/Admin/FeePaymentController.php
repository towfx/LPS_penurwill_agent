<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Agent;
use App\Models\FeePayment;
use App\Services\FeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class FeePaymentController extends Controller
{
    /**
     * List fee payments — optionally scoped to a single agent.
     */
    public function index(Request $request)
    {
        $query = FeePayment::query()->with(['agent', 'recordedBy']);

        if ($request->filled('agent_id')) {
            $query->where('agent_id', $request->agent_id);
        }
        if ($request->filled('fee_type')) {
            $query->where('fee_type', $request->fee_type);
        }

        $payments = $query->orderByDesc('paid_at')->paginate(50);

        return Inertia::render('Admin/FeePayments', [
            'payments' => $payments,
            'filters' => [
                'agent_id' => $request->get('agent_id'),
                'fee_type' => $request->get('fee_type'),
            ],
        ]);
    }

    /**
     * Manually record a fee payment via FeeService.
     */
    public function store(Request $request, FeeService $feeService)
    {
        $data = $request->validate([
            'agent_id' => 'required|exists:agents,id',
            'fee_type' => 'required|in:'.FeePayment::TYPE_ENTRY.','.FeePayment::TYPE_RENEWAL,
            'payment_method' => 'sometimes|in:'.implode(',', [
                FeePayment::METHOD_STRIPE,
                FeePayment::METHOD_BANK_TRANSFER,
                FeePayment::METHOD_MANUAL,
                FeePayment::METHOD_WAIVED,
            ]),
            'payment_reference' => 'nullable|string|max:255',
        ]);

        $admin = Auth::user();
        $agent = Agent::findOrFail($data['agent_id']);
        $method = $data['payment_method'] ?? FeePayment::METHOD_MANUAL;

        $payment = $data['fee_type'] === FeePayment::TYPE_ENTRY
            ? $feeService->applyEntryFee($agent, $admin, $method, $data['payment_reference'] ?? null)
            : $feeService->applyRenewalFee($agent, $admin, $method, $data['payment_reference'] ?? null);

        ActivityLog::logCreate($admin, $payment, $payment->toArray());

        return back()->with('success', "Fee payment recorded for agent #{$agent->id}.");
    }
}
