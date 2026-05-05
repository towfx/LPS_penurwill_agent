<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('system_settings', 'commission_calc_type')) {
                $table->enum('commission_calc_type', ['percentage', 'fixed'])->default('percentage');
            }
            if (! Schema::hasColumn('system_settings', 'commission_fixed_amount')) {
                $table->decimal('commission_fixed_amount', 10, 2)->nullable();
            }
            if (! Schema::hasColumn('system_settings', 'partner_commission_calc_type')) {
                $table->enum('partner_commission_calc_type', ['percentage', 'fixed'])->default('percentage');
            }
            if (! Schema::hasColumn('system_settings', 'partner_commission_fixed_amount')) {
                $table->decimal('partner_commission_fixed_amount', 10, 2)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropColumn([
                'commission_calc_type',
                'commission_fixed_amount',
                'partner_commission_calc_type',
                'partner_commission_fixed_amount',
            ]);
        });
    }
};
