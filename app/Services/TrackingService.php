<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentVisit;
use App\Models\Commission;
use App\Models\Referral;
use App\Models\ReferralCode;
use App\Models\Sale;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class TrackingService
{
    /**
     * Track a new referral
     *
     * @param array $data
     * @param Request $request
     * @return array
     * @throws ValidationException
     */
    public function trackReferral(array $data, Request $request): array
    {
        // Validate request
        $validator = Validator::make($data, [
            'referral_code' => 'required|string|max:50',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:1000',
            'source' => 'nullable|string|max:100',
            'amount' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validatedData = $validator->validated();

        // Find the referral code
        $referralCode = ReferralCode::where('code', $validatedData['referral_code'])
            ->where('is_active', true)
            ->first();

        if (!$referralCode) {
            throw new \Exception('Invalid or inactive referral code', 404);
        }

        // Get the agent
        $agent = $referralCode->agent;
        if (!$agent || $agent->status !== 'active') {
            throw new \Exception('Agent not found or inactive', 404);
        }

        // Check if customer already exists for this agent
        $existingReferral = Referral::where('referrer_id', $agent->id)
            ->where('referred_email', $validatedData['customer_email'])
            ->first();

        if ($existingReferral) {
            throw new \Exception('Customer already referred by this agent', 409);
        }

        DB::beginTransaction();

        try {
            // Create the referral
            $referral = Referral::create([
                'referrer_id' => $agent->id,
                'referred_email' => $validatedData['customer_email'],
                'referred_name' => $validatedData['customer_name'],
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

            return [
                'success' => true,
                'message' => 'Referral tracked successfully',
                'data' => [
                    'referral_id' => $referral->id,
                    'agent_name' => $agent->name,
                    'customer_name' => $referral->customer_name,
                    'status' => $referral->status,
                    'tracked_at' => $referral->tracked_at,
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Track a new visit
     *
     * @param array $data
     * @param Request $request
     * @return array
     * @throws ValidationException
     */
    public function trackVisit(array $data, Request $request): array
    {
        // Validate request
        $validator = Validator::make($data, [
            'referral_code' => 'required|string|max:50',
            'visit_url' => 'required|url|max:500',
            'visit_time' => 'required|date',
            'referral_page' => 'nullable|string|max:255',
            'session_id' => 'nullable|string|max:100',
            'page_title' => 'nullable|string|max:255',
            'user_agent' => 'nullable|string|max:500',
            'screen_resolution' => 'nullable|string|max:50',
            'language' => 'nullable|string|max:10',
            'timezone' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validatedData = $validator->validated();

        // Find the referral code
        $referralCode = ReferralCode::where('code', $validatedData['referral_code'])
            ->where('is_active', true)
            ->first();

        if (!$referralCode) {
            throw new \Exception('Invalid or inactive referral code', 404);
        }

        // Get the agent
        $agent = $referralCode->agent;
        if (!$agent || $agent->status !== 'active') {
            throw new \Exception('Agent not found or inactive', 404);
        }

        DB::beginTransaction();

        try {
            // Create the visit record
            $visit = AgentVisit::create([
                'agent_id' => $agent->id,
                'referral_code' => $validatedData['referral_code'],
                'visit_url' => $validatedData['visit_url'],
                'visit_time' => $validatedData['visit_time'],
                'referral_page' => $validatedData['referral_page'] ?? null,
                'session_id' => $validatedData['session_id'] ?? null,
                'page_title' => $validatedData['page_title'] ?? null,
                'ip_address' => $request->ip(),
                'user_agent' => $validatedData['user_agent'] ?? $request->userAgent(),
                'screen_resolution' => $validatedData['screen_resolution'] ?? null,
                'language' => $validatedData['language'] ?? null,
                'timezone' => $validatedData['timezone'] ?? null,
            ]);

            // Log activity (using system user for API tracking)
            $systemUser = User::where('email', 'system@penurwill.com')->first();
            if ($systemUser) {
                ActivityLog::logCreate($systemUser, $visit, $visit->toArray());
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Visit tracked successfully',
                'data' => [
                    'visit_id' => $visit->id,
                    'agent_name' => $agent->name,
                    'referral_code' => $validatedData['referral_code'],
                    'visit_url' => $validatedData['visit_url'],
                    'visit_time' => $validatedData['visit_time'],
                    'referral_page' => $validatedData['referral_page'],
                    'tracked_at' => $visit->created_at,
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Track a new sale
     *
     * @param array $data
     * @param Request $request
     * @return array
     * @throws ValidationException
     */
    public function trackSale(array $data, Request $request): array
    {
        // Validate request
        $validator = Validator::make($data, [
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
            throw new ValidationException($validator);
        }

        $validatedData = $validator->validated();

        // Find the referral code
        $referralCode = ReferralCode::where('code', $validatedData['referral_code'])
            ->where('is_active', true)
            ->first();

        if (!$referralCode) {
            throw new \Exception('Invalid or inactive referral code', 404);
        }

        // Get the agent
        $agent = $referralCode->agent;
        if (!$agent || $agent->status !== 'active') {
            throw new \Exception('Agent not found or inactive', 404);
        }

        DB::beginTransaction();

        try {
            // Calculate commission amount before creating sale
            $commissionRate = $agent->commissionRate;
            $commissionPercentage = $commissionRate ? $commissionRate->custom_rate : 10; // Default 10%
            $commissionAmount = ($validatedData['sale_amount'] * $commissionPercentage) / 100;

            // Create the sale
            $sale = Sale::create([
                'agent_id' => $agent->id,
                'amount' => $validatedData['sale_amount'],
                'commission_amount' => $commissionAmount,
                'sale_date' => $validatedData['sale_date'],
                'buyer_email' => $validatedData['customer_email'],
                'description' => $validatedData['product_name'],
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

            return [
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
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get referral code information
     *
     * @param string $code
     * @return array
     */
    public function getReferralCodeInfo(string $code): array
    {
        $referralCode = ReferralCode::where('code', $code)
            ->where('is_active', true)
            ->with('agent')
            ->first();

        if (!$referralCode) {
            throw new \Exception('Referral code not found or inactive', 404);
        }

        $agent = $referralCode->agent;
        if (!$agent || $agent->status !== 'active') {
            throw new \Exception('Agent not found or inactive', 404);
        }

        return [
            'success' => true,
            'data' => [
                'referral_code' => $referralCode->code,
                'agent_name' => $agent->name,
                'agent_type' => $agent->type,
                'is_active' => $referralCode->is_active,
                'created_at' => $referralCode->created_at,
            ]
        ];
    }

    /**
     * Get API version information
     *
     * @return array
     */
    public function getVersion(): array
    {
        return [
            'success' => true,
            'data' => [
                'version' => '1.0.0',
                'name' => 'Penurwill Agent Tracking API',
                'description' => 'API for tracking agent referrals, visits, and sales',
                'endpoints' => [
                    'track_referral' => 'POST /api/agents/track/referral',
                    'track_visit' => 'POST /api/agents/track/visit',
                    'track_sale' => 'POST /api/agents/track/sale',
                    'get_referral_info' => 'GET /api/agents/track/code/{code}',
                    'get_version' => 'GET /api/agents/track/version',
                ],
                'features' => [
                    'cross_domain_tracking' => true,
                    'pixel_tracking' => true,
                    'session_tracking' => true,
                    'activity_logging' => true,
                ],
                'timestamp' => now()->toISOString(),
            ]
        ];
    }
} 