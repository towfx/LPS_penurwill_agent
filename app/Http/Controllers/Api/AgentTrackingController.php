<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TrackingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class AgentTrackingController extends Controller
{
    protected $trackingService;

    public function __construct(TrackingService $trackingService)
    {
        $this->trackingService = $trackingService;
    }

    /**
     * Track a new referral
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function trackReferral(Request $request): JsonResponse
    {
        try {
            $result = $this->trackingService->trackReferral($request->all(), $request);
            return response()->json($result, 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            $statusCode = $e->getCode() ?: 500;
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : null
            ], $statusCode);
        }
    }

    /**
     * Track a new visit
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function trackVisit(Request $request): JsonResponse
    {
        try {
            $result = $this->trackingService->trackVisit($request->all(), $request);
            return response()->json($result, 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            $statusCode = $e->getCode() ?: 500;
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : null
            ], $statusCode);
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
            $result = $this->trackingService->trackSale($request->all(), $request);
            return response()->json($result, 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            $statusCode = $e->getCode() ?: 500;
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : null
            ], $statusCode);
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
            $result = $this->trackingService->getReferralCodeInfo($code);
            return response()->json($result);
        } catch (\Exception $e) {
            $statusCode = $e->getCode() ?: 500;
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : null
            ], $statusCode);
        }
    }

    /**
     * Get API version information
     *
     * @return JsonResponse
     */
    public function getVersion(): JsonResponse
    {
        try {
            $result = $this->trackingService->getVersion();
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
