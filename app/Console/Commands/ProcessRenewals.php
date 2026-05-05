<?php

namespace App\Console\Commands;

use App\Services\RenewalService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessRenewals extends Command
{
    protected $signature = 'penurwill:process-renewals';

    protected $description = 'Send renewal reminders, mark expired agents, and send expiry alerts.';

    public function handle(RenewalService $renewals): int
    {
        $error = null;
        try {
            $reminders = $renewals->sendRenewalReminders();
            $expired = $renewals->markExpiredAgents();
            $alerts = $renewals->sendExpiryAlerts();

            $this->info("Reminders sent: {$reminders}, agents expired: {$expired}, alerts sent: {$alerts}");
            $status = 'success';
        } catch (\Throwable $e) {
            $error = $e->getMessage();
            $status = 'failed';
            $this->error('ProcessRenewals failed: '.$error);
        }

        $this->writeLog($status, $error);

        return $status === 'success' ? self::SUCCESS : self::FAILURE;
    }

    protected function writeLog(string $status, ?string $errorMessage): void
    {
        try {
            DB::table('scheduler_logs')->insert([
                'job_type' => 'process_renewals',
                'status' => $status,
                'ran_at' => now(),
                'error_message' => $errorMessage,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // scheduler_logs table may not exist yet in tests
        }
    }
}
