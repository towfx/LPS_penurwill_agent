<?php
/**
 * Date: 2026-05-16
 * Task: Remove unused "Renewal Enabled" toggles from system_settings.
 */

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
            if (Schema::hasColumn('system_settings', 'renewal_fee_leader_enabled')) {
                $table->dropColumn('renewal_fee_leader_enabled');
            }
            if (Schema::hasColumn('system_settings', 'renewal_fee_agent_enabled')) {
                $table->dropColumn('renewal_fee_agent_enabled');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->boolean('renewal_fee_leader_enabled')->default(true)->after('renewal_fee_leader');
            $table->boolean('renewal_fee_agent_enabled')->default(true)->after('renewal_fee_agent');
        });
    }
};
