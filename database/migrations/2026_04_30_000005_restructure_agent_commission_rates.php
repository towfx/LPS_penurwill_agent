<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // doctrine/dbal is required for renameColumn on some platforms; use raw add+copy+drop on SQLite
        $driver = DB::connection()->getDriverName();

        Schema::table('agent_commission_rates', function (Blueprint $table) {
            if (! Schema::hasColumn('agent_commission_rates', 'kind')) {
                $table->enum('kind', ['own_sales', 'override_agent', 'override_agent_leader'])
                    ->default('own_sales')
                    ->after('agent_id');
            }
            if (! Schema::hasColumn('agent_commission_rates', 'custom_percentage')) {
                $table->decimal('custom_percentage', 5, 2)->nullable()->after('kind');
            }
            if (! Schema::hasColumn('agent_commission_rates', 'custom_fixed_amount')) {
                $table->decimal('custom_fixed_amount', 10, 2)->default(0)->after('custom_percentage');
            }
            if (! Schema::hasColumn('agent_commission_rates', 'commission_calc_type')) {
                $table->enum('commission_calc_type', ['percentage', 'fixed'])
                    ->default('percentage')
                    ->after('custom_fixed_amount');
            }
        });

        // Copy legacy custom_rate values into custom_percentage if both exist.
        if (Schema::hasColumn('agent_commission_rates', 'custom_rate')) {
            DB::statement('UPDATE agent_commission_rates SET custom_percentage = custom_rate WHERE custom_percentage IS NULL');
            Schema::table('agent_commission_rates', function (Blueprint $table) {
                $table->dropColumn('custom_rate');
            });
        }

        // Unique (agent_id, kind)
        Schema::table('agent_commission_rates', function (Blueprint $table) {
            $table->unique(['agent_id', 'kind'], 'agent_commission_rates_agent_kind_unique');
        });
    }

    public function down(): void
    {
        Schema::table('agent_commission_rates', function (Blueprint $table) {
            $table->dropUnique('agent_commission_rates_agent_kind_unique');
            $table->dropColumn(['kind', 'custom_fixed_amount', 'commission_calc_type']);
            // Re-add legacy column
            if (! Schema::hasColumn('agent_commission_rates', 'custom_rate')) {
                $table->decimal('custom_rate', 5, 2)->nullable();
            }
        });

        if (Schema::hasColumn('agent_commission_rates', 'custom_percentage')) {
            DB::statement('UPDATE agent_commission_rates SET custom_rate = custom_percentage');
            Schema::table('agent_commission_rates', function (Blueprint $table) {
                $table->dropColumn('custom_percentage');
            });
        }
    }
};
