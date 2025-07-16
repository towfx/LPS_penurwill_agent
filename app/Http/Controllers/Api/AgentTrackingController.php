<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Commission;
use App\Models\Referral;
use App\Models\ReferralCode;
use App\Models\Sale;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AgentTrackingController extends Controller
{
    /**
     * Track a new referral
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function trackReferral(Request $request): JsonResponse
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'referral_code' => 'required|string|max:50',
                'customer_name' => 'required|string|max:255',
                'customer_email' => 'required|email|max:255',
                'customer_phone' => 'nullable|string|max:20',
                'notes' => 'nullable|string|max:1000',
                'source' => 'nullable|string|max:100',
                'amount' => 'nullable|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();

            // Find the referral code
            $referralCode = ReferralCode::where('code', $data['referral_code'])
                ->where('is_active', true)
                ->first();

            if (!$referralCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or inactive referral code'
                ], 404);
            }

            // Get the agent
            $agent = $referralCode->agent;
            if (!$agent || !$agent->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Agent not found or inactive'
                ], 404);
            }

            // Check if customer already exists for this agent
            $existingReferral = Referral::where('referrer_id', $agent->id)
                ->where('referred_email', $data['customer_email'])
                ->first();

            if ($existingReferral) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer already referred by this agent'
                ], 409);
            }

            DB::beginTransaction();

            try {
                // Create the referral
                $referral = Referral::create([
                    'referrer_id' => $agent->id,
                    'referred_email' => $data['customer_email'],
                    'referred_name' => $data['customer_name'],
                    'status' => 'pending',
                    'landing_page_url' => $request->input('landing_page_url'),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                // Log activity (using system user for API tracking)
                $systemUser = User::where('email', 'system@penurwill.com')->first();
                if ($systemUser) {
                    ActivityLog::logCreate($systemUser, $referral, $referral->toArray());
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Referral tracked successfully',
                    'data' => [
                        'referral_id' => $referral->id,
                        'agent_name' => $agent->name,
                        'customer_name' => $referral->customer_name,
                        'status' => $referral->status,
                        'tracked_at' => $referral->tracked_at,
                    ]
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Track a new sale
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function trackSale(Request $request): JsonResponse
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'referral_code' => 'required|string|max:50',
                'customer_name' => 'required|string|max:255',
                'customer_email' => 'required|email|max:255',
                'customer_phone' => 'nullable|string|max:20',
                'sale_amount' => 'required|numeric|min:0.01',
                'product_name' => 'required|string|max:255',
                'sale_date' => 'required|date|before_or_equal:today',
                'notes' => 'nullable|string|max:1000',
                'source' => 'nullable|string|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();

            // Find the referral code
            $referralCode = ReferralCode::where('code', $data['referral_code'])
                ->where('is_active', true)
                ->first();

            if (!$referralCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or inactive referral code'
                ], 404);
            }

            // Get the agent
            $agent = $referralCode->agent;
            if (!$agent || $agent->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Agent not found or inactive'
                ], 404);
            }

            DB::beginTransaction();

            try {
                // Calculate commission amount before creating sale
                $commissionRate = $agent->commissionRate;
                $commissionPercentage = $commissionRate ? $commissionRate->custom_rate : 10; // Default 10%
                $commissionAmount = ($data['sale_amount'] * $commissionPercentage) / 100;

                // Create the sale
                $sale = Sale::create([
                    'agent_id' => $agent->id,
                    'amount' => $data['sale_amount'],
                    'commission_amount' => $commissionAmount,
                    'sale_date' => $data['sale_date'],
                    'buyer_email' => $data['customer_email'],
                    'description' => $data['product_name'],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                // Create commission
                $commission = Commission::create([
                    'commission_source' => $commissionRate ? 'agent_rate' : 'system_default',
                    'applied_rate' => $commissionPercentage,
                    'sale_id' => $sale->id,
                    'agent_id' => $agent->id,
                    'commission_rate' => $commissionPercentage,
                    'amount' => $commissionAmount,
                    'status' => 'pending',
                ]);

                // Log activity (using system user for API tracking)
                $systemUser = User::where('email', 'system@penurwill.com')->first();
                if ($systemUser) {
                    ActivityLog::logCreate($systemUser, $sale, $sale->toArray());
                    ActivityLog::logCreate($systemUser, $commission, $commission->toArray());
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Sale tracked successfully',
                    'data' => [
                        'sale_id' => $sale->id,
                        'commission_id' => $commission->id,
                        'agent_name' => $agent->name,
                        'customer_name' => $sale->customer_name,
                        'sale_amount' => $sale->sale_amount,
                        'commission_amount' => $commission->amount,
                        'commission_percentage' => $commission->percentage,
                        'status' => $sale->status,
                        'tracked_at' => $sale->tracked_at,
                    ]
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get referral code information
     *
     * @param string $code
     * @return JsonResponse
     */
    public function getReferralCodeInfo(string $code): JsonResponse
    {
        try {
            $referralCode = ReferralCode::where('code', $code)
                ->where('is_active', true)
                ->with('agent')
                ->first();

            if (!$referralCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Referral code not found or inactive'
                ], 404);
            }

            $agent = $referralCode->agent;
            if (!$agent || !$agent->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Agent not found or inactive'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'referral_code' => $referralCode->code,
                    'agent_name' => $agent->name,
                    'agent_type' => $agent->type,
                    'is_active' => $referralCode->is_active,
                    'created_at' => $referralCode->created_at,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
