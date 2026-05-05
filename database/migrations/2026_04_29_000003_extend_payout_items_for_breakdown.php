<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payout_items', function (Blueprint $table) {
            if (! Schema::hasColumn('payout_items', 'commission_type')) {
                $table->enum('commission_type', ['own_sales', 'override'])
                    ->nullable()
                    ->after('commission_id');
            }
            if (! Schema::hasColumn('payout_items', 'commission_category')) {
                $table->enum('commission_category', ['business_partner', 'agent_leader', 'agent'])
                    ->nullable()
                    ->after('commission_type');
            }
        });

        Schema::table('payout_items', function (Blueprint $table) {
            $table->index(['payout_id', 'commission_type'], 'payout_items_payout_type_index');
        });
    }

    public function down(): void
    {
        Schema::table('payout_items', function (Blueprint $table) {
            $table->dropIndex('payout_items_payout_type_index');
            $table->dropColumn(['commission_type', 'commission_category']);
        });
    }
};
