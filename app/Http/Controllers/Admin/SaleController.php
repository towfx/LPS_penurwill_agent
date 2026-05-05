<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\ReversalWindowExpiredException;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Sale;
use App\Services\RefundService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SaleController extends Controller
{
    /**
     * Mark a sale as refunded — reverses every commission tied to it via RefundService.
     */
    public function markAsRefunded(Request $request, Sale $sale, RefundService $refundService)
    {
        $admin = Auth::user();

        try {
            $reversals = $refundService->reverseSale($sale, $admin);
        } catch (ReversalWindowExpiredException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to refund sale: '.$e->getMessage()]);
        }

        ActivityLog::logCustom(
            $admin,
            'sale_refunded',
            "Admin marked sale #{$sale->id} as refunded; {$reversals->count()} commission(s) reversed.",
            $sale,
        );

        return back()->with('success', "Sale refunded. {$reversals->count()} commission(s) reversed.");
    }
}
