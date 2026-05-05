<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commissions', function (Blueprint $table) {
            if (! Schema::hasColumn('commissions', 'earning_agent_id')) {
                $table->foreignId('earning_agent_id')->nullable()->after('agent_id')
                    ->constrained('agents')->nullOnDelete();
            }
            if (! Schema::hasColumn('commissions', 'commission_type')) {
                $table->enum('commission_type', ['own_sales', 'override'])
                    ->default('own_sales')
                    ->after('commission_source');
            }
            if (! Schema::hasColumn('commissions', 'commission_category')) {
                $table->enum('commission_category', ['business_partner', 'agent_leader', 'agent'])
                    ->nullable()
                    ->after('commission_type');
            }
        });

        Schema::table('commissions', function (Blueprint $table) {
            $table->index(['earning_agent_id', 'commission_type'], 'commissions_earner_type_index');
            $table->index('commission_category', 'commissions_category_index');
        });
    }

    public function down(): void
    {
        Schema::table('commissions', function (Blueprint $table) {
            $table->dropIndex('commissions_earner_type_index');
            $table->dropIndex('commissions_category_index');
            if (Schema::hasColumn('commissions', 'earning_agent_id')) {
                $table->dropConstrainedForeignId('earning_agent_id');
            }
            $table->dropColumn(['commission_type', 'commission_category']);
        });
    }
};
