<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TrackingService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class TrackingPixelController extends Controller
{
    protected $trackingService;

    public function __construct(TrackingService $trackingService)
    {
        $this->trackingService = $trackingService;
    }

    /**
     * Handle tracking pixel requests for cross-domain tracking
     *
     * @param Request $request
     * @return Response
     */
    public function track(Request $request): Response
    {
        try {
            // Extract parameters from query string or headers
            $data = $this->extractTrackingData($request);
            
            // Track the visit
            $this->trackingService->trackVisit($data, $request);

            // Return a 1x1 transparent GIF pixel
            return $this->createPixelResponse();

        } catch (ValidationException $e) {
            // Still return pixel even if validation fails to avoid breaking the page
            return $this->createPixelResponse();
        } catch (\Exception $e) {
            // Log error but still return pixel to avoid breaking the page
            Log::error('Tracking pixel error: ' . $e->getMessage());
            return $this->createPixelResponse();
        }
    }

    /**
     * Extract tracking data from request
     *
     * @param Request $request
     * @return array
     */
    private function extractTrackingData(Request $request): array
    {
        $data = [];

        // Required parameters
        $data['referral_code'] = $request->get('rc') ?: $request->get('referral_code');
        $data['visit_url'] = $request->get('url') ?: $request->get('visit_url');
        $data['visit_time'] = $request->get('t') ?: $request->get('visit_time', now()->toISOString());

        // Optional parameters
        $data['referral_page'] = $request->get('ref') ?: $request->get('referral_page');
        $data['session_id'] = $request->get('sid') ?: $request->get('session_id');
        $data['page_title'] = $request->get('title') ?: $request->get('page_title');
        $data['user_agent'] = $request->get('ua') ?: $request->get('user_agent');
        $data['screen_resolution'] = $request->get('sr') ?: $request->get('screen_resolution');
        $data['language'] = $request->get('lang') ?: $request->get('language');
        $data['timezone'] = $request->get('tz') ?: $request->get('timezone');

        // Filter out null values
        return array_filter($data, function ($value) {
            return $value !== null && $value !== '';
        });
    }

    /**
     * Create a 1x1 transparent GIF pixel response
     *
     * @return Response
     */
    private function createPixelResponse(): Response
    {
        // 1x1 transparent GIF pixel
        $pixelData = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

        return response($pixelData, 200, [
            'Content-Type' => 'image/gif',
            'Content-Length' => strlen($pixelData),
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, X-Requested-With',
        ]);
    }

    /**
     * Handle preflight CORS requests
     *
     * @param Request $request
     * @return Response
     */
    public function preflight(Request $request): Response
    {
        return response('', 200, [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, X-Requested-With',
            'Access-Control-Max-Age' => '86400',
        ]);
    }
}
