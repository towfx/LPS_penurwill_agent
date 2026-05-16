<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            foreach ([
                'agent_own_sales',
                'agent_leader_own_sales',
                'agent_leader_override_agent',
                'business_partner_own_sales',
                'business_partner_override_agent',
                'business_partner_override_agent_leader',
            ] as $key) {
                $table->enum("{$key}_calc_type", ['percentage', 'fixed'])->default('percentage')->after("{$key}_fixed_amount");
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropColumn([
                'agent_own_sales_calc_type',
                'agent_leader_own_sales_calc_type',
                'agent_leader_override_agent_calc_type',
                'business_partner_own_sales_calc_type',
                'business_partner_override_agent_calc_type',
                'business_partner_override_agent_leader_calc_type',
            ]);
        });
    }
};
