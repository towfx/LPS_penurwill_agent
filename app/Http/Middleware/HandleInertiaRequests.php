<?php

namespace App\Http\Middleware;

use App\Models\AgentNotification;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user(),
                'roles' => $request->user()?->getRoleNames() ?? [],
            ],
            'flash' => [
                'success' => session('success'),
                'error' => session('error'),
            ],
            'systemSettings' => fn () => $this->systemSettings(),
            'agentContext' => fn () => $this->agentContext($request),
        ]);
    }

    /**
     * Expose role-name labels and key flags so Vue can render them
     * without hardcoding (Decision 15). Cached per request via the closure.
     */
    protected function systemSettings(): array
    {
        try {
            $settings = \App\Models\SystemSetting::first();
        } catch (\Throwable $e) {
            return [];
        }
        if (! $settings) {
            return [];
        }

        return [
            'role_name_agent' => $settings->role_name_agent ?? 'Agent',
            'role_name_leader' => $settings->role_name_leader ?? 'Agent Leader',
            'role_name_business_partner' => $settings->role_name_business_partner ?? 'Business Partner',
            'min_payout_amount' => $settings->min_payout_amount ?? null,
            'reversal_time_limit' => $settings->reversal_time_limit ?? null,
            'membership_duration_days' => $settings->membership_duration_days ?? null,
            'renewal_reminder_days_before' => $settings->renewal_reminder_days_before ?? null,
            'referral_code_prefix' => $settings->referral_code_prefix ?? null,
        ];
    }

    /**
     * Share agent-specific context (unread count, status flags) for dashboard banners.
     * Returns empty array for non-agents or unauthenticated requests.
     */
    protected function agentContext(Request $request): array
    {
        try {
            $user = $request->user();
            if (! $user || ! $user->hasRole('agent')) {
                return [];
            }

            $agent = $user->agents()->first();
            if (! $agent) {
                return [];
            }

            $unreadCount = AgentNotification::forAgent($agent->id)->unread()->count();

            return [
                'unread_inbox_count' => $unreadCount,
                'agent_status' => $agent->status,
                'fee_payment_status' => $agent->fee_payment_status,
            ];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Handle the response for the given request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Illuminate\Http\Response
     */
    public function handle(Request $request, \Closure $next)
    {
        $response = parent::handle($request, $next);

        // Handle 403 errors
        if ($response->getStatusCode() === 403) {
            return \Inertia\Inertia::render('Error/403');
        }

        return $response;
    }
}
