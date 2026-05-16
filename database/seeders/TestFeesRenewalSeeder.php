<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\FeePayment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Backdates fee_payments and distributes agent renewal/expiry dates so that
 * `php artisan penurwill:process-renewals` triggers reminders, alerts, and
 * expirations against existing data.
 */
class TestFeesRenewalSeeder extends Seeder
{
    private const PAYMENT_REFERENCE = 'TEST_SEED';

    public function run(): void
    {
        $today = Carbon::today();
        $registeredAt = $today->copy()->subDays(365);

        DB::transaction(function () use ($today, $registeredAt) {
            FeePayment::where('payment_reference', self::PAYMENT_REFERENCE)->delete();

            $agents = Agent::query()
                ->where(function ($q) {
                    $q->where('is_default', false)->orWhereNull('is_default');
                })
                ->orderBy('id')
                ->get();

            $counts = ['A_reminder' => 0, 'B_pending' => 0, 'C_alert' => 0, 'D_expired' => 0];

            foreach ($agents as $index => $agent) {
                $bucket = $this->bucketFor($index);

                [$renewalDueAt, $expiresAt, $statusAfterReset] = $this->datesFor($bucket, $index, $today);

                $agent->forceFill([
                    'status' => $statusAfterReset,
                    'fee_payment_status' => Agent::FEE_STATUS_PENDING,
                    'registered_at' => $registeredAt->toDateString(),
                    'renewal_due_at' => $renewalDueAt,
                    'expires_at' => $expiresAt,
                ])->save();

                FeePayment::create([
                    'agent_id' => $agent->id,
                    'fee_type' => FeePayment::TYPE_ENTRY,
                    'role' => $agent->agent_role ?? Agent::ROLE_AGENT,
                    'amount' => 100,
                    'payment_method' => FeePayment::METHOD_MANUAL,
                    'payment_reference' => self::PAYMENT_REFERENCE,
                    'status' => FeePayment::STATUS_CONFIRMED,
                    'paid_at' => $registeredAt,
                    'recorded_by' => null,
                ]);

                $counts[$bucket]++;
            }

            $this->command?->info('TestFeesRenewalSeeder distribution:');
            $this->command?->line("  A reminder due today : {$counts['A_reminder']}");
            $this->command?->line("  B pending 1-29 days  : {$counts['B_pending']}");
            $this->command?->line("  C expiry alert today : {$counts['C_alert']}");
            $this->command?->line("  D already expired    : {$counts['D_expired']}");
        });
    }

    /**
     * First 3 agents: due today (A_reminder).
     * Next 3 agents: past due / expiry alert (C_alert).
     * Next 3 agents: already expired (D_expired).
     * Remaining: deterministic 20-slot cycle for B_pending / D_expired.
     */
    private function bucketFor(int $index): string
    {
        if ($index < 3) {
            return 'A_reminder';
        }

        if ($index < 6) {
            return 'C_alert';
        }

        if ($index < 9) {
            return 'D_expired';
        }

        $slot = ($index - 9) % 20;
        return match (true) {
            $slot < 16 => 'B_pending',
            default    => 'D_expired',
        };
    }

    /**
     * Returns [renewal_due_at, expires_at, status].
     * Status is reset to 'active' for every bucket so previously-expired agents
     * (from earlier seed runs) are eligible to flip to 'expired' again.
     */
    private function datesFor(string $bucket, int $index, Carbon $today): array
    {
        return match ($bucket) {
            'A_reminder' => [
                $today->toDateString(),
                $today->copy()->addDays(30)->toDateString(),
                'active',
            ],
            'B_pending' => (function () use ($index, $today) {
                $n = ($index % 29) + 1;
                return [
                    $today->copy()->addDays($n)->toDateString(),
                    $today->copy()->addDays($n + 30)->toDateString(),
                    'active',
                ];
            })(),
            'C_alert' => [
                $today->copy()->subDays(30)->toDateString(),
                $today->toDateString(),
                'active',
            ],
            'D_expired' => (function () use ($index, $today) {
                $k = ($index % 10) + 1;
                return [
                    $today->copy()->subDays(30 + $k)->toDateString(),
                    $today->copy()->subDays($k)->toDateString(),
                    'active',
                ];
            })(),
        };
    }
}
