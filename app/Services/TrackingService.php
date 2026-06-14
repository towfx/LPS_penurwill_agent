<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Agent;
use App\Models\AgentVisit;
use App\Models\Commission;
use App\Models\Referral;
use App\Models\ReferralCode;
use App\Models\Sale;
use App\Support\SystemUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class TrackingService
{
    public function __construct(
        protected ?CommissionGenerator $commissionGenerator = null,
        protected ?CommissionCalculator $commissionCalculator = null,
    ) {}

    /**
     * Track a new referral
     *
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

        if (! $referralCode) {
            throw new \Exception('Invalid or inactive referral code', 404);
        }

        // Get the agent
        $agent = $referralCode->agent;
        if (! $agent || $agent->status !== 'active') {
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

            // Update used count
            $referralCode->increment('used_count');

            // Log activity (using system user for API tracking)
            $systemUser = SystemUser::resolve();
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
                ],
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Track a new visit
     *
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

        if (! $referralCode) {
            throw new \Exception('Invalid or inactive referral code', 404);
        }

        // Get the agent
        $agent = $referralCode->agent;
        if (! $agent || $agent->status !== 'active') {
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
            $systemUser = SystemUser::resolve();
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
                ],
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Track a new sale
     *
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
            'invoice_number' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validatedData = $validator->validated();

        // Find the referral code
        $referralCode = ReferralCode::where('code', $validatedData['referral_code'])
            ->where('is_active', true)
            ->first();

        if (! $referralCode) {
            throw new \Exception('Invalid or inactive referral code', 404);
        }

        // Get the agent
        $agent = $referralCode->agent;
        if (! $agent || $agent->status !== 'active') {
            throw new \Exception('Agent not found or inactive', 404);
        }

        // Deduplicate by invoice_number when provided
        if (! empty($validatedData['invoice_number'])) {
            $existing = Sale::where('agent_id', $agent->id)
                ->where('invoice_number', $validatedData['invoice_number'])
                ->first();

            if ($existing) {
                $systemUser = SystemUser::resolve();
                if ($systemUser) {
                    ActivityLog::logCustom(
                        $systemUser,
                        'duplicate_sale_skipped',
                        "Duplicate sale skipped for invoice {$validatedData['invoice_number']}",
                        $existing,
                    );
                }

                $primaryCommission = $existing->commissions->firstWhere('commission_type', 'own_sales')
                    ?? $existing->commissions->first();

                return [
                    'success' => true,
                    'message' => 'Sale already tracked',
                    'data' => [
                        'sale_id' => $existing->id,
                        'commission_id' => $primaryCommission?->id,
                        'commission_ids' => $existing->commissions->pluck('id')->all(),
                        'agent_name' => $agent->name,
                        'customer_name' => $validatedData['customer_name'],
                        'sale_amount' => $existing->amount,
                        'commission_amount' => $primaryCommission?->amount,
                        'commission_percentage' => $primaryCommission?->commission_rate,
                        'commission_total' => $existing->commissions->sum('amount'),
                        'status' => $existing->commissions->first()?->status ?? 'pending',
                        'tracked_at' => $existing->created_at,
                    ],
                ];
            }
        }

        DB::beginTransaction();

        try {
            $generator = $this->commissionGenerator ?? app(CommissionGenerator::class);
            $calculator = $this->commissionCalculator ?? app(CommissionCalculator::class);

            $rate = $calculator->getApplicableRate($agent, CommissionCalculator::KIND_OWN_SALES);
            $estimatedCommission = $calculator->calculate(
                (float) $validatedData['sale_amount'],
                (float) $rate['percentage'],
                (float) $rate['fixed_amount'],
                $rate['calc_type'],
            );

            $sale = Sale::create([
                'agent_id' => $agent->id,
                'amount' => $validatedData['sale_amount'],
                'commission_amount' => $estimatedCommission,
                'sale_date' => $validatedData['sale_date'],
                'buyer_email' => $validatedData['customer_email'],
                'description' => $validatedData['product_name'],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'invoice_number' => $validatedData['invoice_number'] ?? null,
            ]);

            $commissions = $generator->generateForSale($sale);

            // Update used count
            $referralCode->increment('used_count');

            $systemUser = SystemUser::resolve();
            if ($systemUser) {
                ActivityLog::logCreate($systemUser, $sale, $sale->toArray());
            }

            DB::commit();

            $primaryCommission = $commissions->firstWhere('commission_type', 'own_sales')
                ?? $commissions->first();

            return [
                'success' => true,
                'message' => 'Sale tracked successfully',
                'data' => [
                    'sale_id' => $sale->id,
                    'commission_id' => $primaryCommission?->id,
                    'commission_ids' => $commissions->pluck('id')->all(),
                    'agent_name' => $agent->name,
                    'customer_name' => $validatedData['customer_name'],
                    'sale_amount' => $sale->amount,
                    'commission_amount' => $primaryCommission?->amount,
                    'commission_percentage' => $primaryCommission?->commission_rate,
                    'commission_total' => $commissions->sum('amount'),
                    'status' => 'pending',
                    'tracked_at' => $sale->created_at,
                ],
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get referral code information
     */
    public function getReferralCodeInfo(string $code): array
    {
        $referralCode = ReferralCode::where('code', $code)
            ->where('is_active', true)
            ->with('agent')
            ->first();

        if (! $referralCode) {
            throw new \Exception('Referral code not found or inactive', 404);
        }

        $agent = $referralCode->agent;
        if (! $agent || $agent->status !== 'active') {
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
            ],
        ];
    }

    /**
     * Get API version information
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
            ],
        ];
    }
}
