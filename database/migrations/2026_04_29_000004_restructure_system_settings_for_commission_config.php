<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            if (Schema::hasColumn('system_settings', 'commission_default_rate')) {
                $table->dropColumn('commission_default_rate');
            }
            if (Schema::hasColumn('system_settings', 'partner_default_commission_rate')) {
                $table->dropColumn('partner_default_commission_rate');
            }
        });

        Schema::table('system_settings', function (Blueprint $table) {
            $rateKeys = [
                'agent_own_sales',
                'agent_leader_own_sales',
                'agent_leader_override_agent',
                'business_partner_own_sales',
                'business_partner_override_agent',
                'business_partner_override_agent_leader',
            ];

            foreach ($rateKeys as $key) {
                if (! Schema::hasColumn('system_settings', "{$key}_percentage")) {
                    $table->decimal("{$key}_percentage", 5, 2)->default(0);
                }
                if (! Schema::hasColumn('system_settings', "{$key}_fixed_amount")) {
                    $table->decimal("{$key}_fixed_amount", 10, 2)->default(0);
                }
            }

            if (! Schema::hasColumn('system_settings', 'skip_zero_commissions')) {
                $table->boolean('skip_zero_commissions')->default(true);
            }
            if (! Schema::hasColumn('system_settings', 'reversal_time_limit')) {
                $table->integer('reversal_time_limit')->default(60);
            }
            if (! Schema::hasColumn('system_settings', 'email_verification_max_retry')) {
                $table->integer('email_verification_max_retry')->default(10);
            }
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $rateKeys = [
                'agent_own_sales',
                'agent_leader_own_sales',
                'agent_leader_override_agent',
                'business_partner_own_sales',
                'business_partner_override_agent',
                'business_partner_override_agent_leader',
            ];
            foreach ($rateKeys as $key) {
                $table->dropColumn(["{$key}_percentage", "{$key}_fixed_amount"]);
            }
            $table->dropColumn(['skip_zero_commissions', 'reversal_time_limit', 'email_verification_max_retry']);
            $table->decimal('commission_default_rate', 5, 2)->default(10.00);
            $table->decimal('partner_default_commission_rate', 5, 2)->default(0);
        });
    }
};
