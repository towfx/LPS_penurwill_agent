<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('system_settings', 'entry_fee_business_partner')) {
                $table->decimal('entry_fee_business_partner', 10, 2)->default(3000.00);
            }
            if (! Schema::hasColumn('system_settings', 'renewal_fee_business_partner')) {
                $table->decimal('renewal_fee_business_partner', 10, 2)->default(1000.00);
            }
            if (! Schema::hasColumn('system_settings', 'entry_fee_leader')) {
                $table->decimal('entry_fee_leader', 10, 2)->default(100.00);
            }
            if (! Schema::hasColumn('system_settings', 'renewal_fee_leader')) {
                $table->decimal('renewal_fee_leader', 10, 2)->default(100.00);
            }
            if (! Schema::hasColumn('system_settings', 'renewal_fee_leader_enabled')) {
                $table->boolean('renewal_fee_leader_enabled')->default(true);
            }
            if (! Schema::hasColumn('system_settings', 'entry_fee_agent')) {
                $table->decimal('entry_fee_agent', 10, 2)->default(100.00);
            }
            if (! Schema::hasColumn('system_settings', 'renewal_fee_agent')) {
                $table->decimal('renewal_fee_agent', 10, 2)->default(100.00);
            }
            if (! Schema::hasColumn('system_settings', 'renewal_fee_agent_enabled')) {
                $table->boolean('renewal_fee_agent_enabled')->default(true);
            }
            if (! Schema::hasColumn('system_settings', 'renewal_reminder_days_before')) {
                $table->integer('renewal_reminder_days_before')->default(30);
            }
            if (! Schema::hasColumn('system_settings', 'membership_duration_days')) {
                $table->integer('membership_duration_days')->default(365);
            }
            if (! Schema::hasColumn('system_settings', 'role_name_agent')) {
                $table->string('role_name_agent', 100)->default('Agent');
            }
            if (! Schema::hasColumn('system_settings', 'role_name_leader')) {
                $table->string('role_name_leader', 100)->default('Leader');
            }
            if (! Schema::hasColumn('system_settings', 'role_name_business_partner')) {
                $table->string('role_name_business_partner', 100)->default('Business Partner');
            }
            // referral_code_prefix already exists in the base schema; only add a default
            if (! Schema::hasColumn('system_settings', 'referral_code_prefix')) {
                $table->string('referral_code_prefix', 50)->default('PENURWILL-');
            }
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropColumn([
                'entry_fee_business_partner',
                'renewal_fee_business_partner',
                'entry_fee_leader',
                'renewal_fee_leader',
                'renewal_fee_leader_enabled',
                'entry_fee_agent',
                'renewal_fee_agent',
                'renewal_fee_agent_enabled',
                'renewal_reminder_days_before',
                'membership_duration_days',
                'role_name_agent',
                'role_name_leader',
                'role_name_business_partner',
            ]);
        });
    }
};
