<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commissions', function (Blueprint $table) {
            if (! Schema::hasColumn('commissions', 'commission_calc_type')) {
                $table->enum('commission_calc_type', ['percentage', 'fixed'])
                    ->default('percentage')
                    ->after('commission_source');
            }
            if (! Schema::hasColumn('commissions', 'commission_fixed_amount')) {
                $table->decimal('commission_fixed_amount', 10, 2)->nullable()->after('commission_calc_type');
            }
            if (! Schema::hasColumn('commissions', 'source_sale_amount')) {
                $table->decimal('source_sale_amount', 10, 2)->nullable()->after('commission_fixed_amount');
            }
            if (! Schema::hasColumn('commissions', 'beneficiary_role')) {
                $table->string('beneficiary_role', 50)->nullable()->after('source_sale_amount');
            }
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE commissions MODIFY status ENUM('pending','approved','paid','cancelled') DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        Schema::table('commissions', function (Blueprint $table) {
            $table->dropColumn([
                'commission_calc_type',
                'commission_fixed_amount',
                'source_sale_amount',
                'beneficiary_role',
            ]);
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE commissions MODIFY status ENUM('pending','approved','paid') DEFAULT 'pending'");
        }
    }
};
