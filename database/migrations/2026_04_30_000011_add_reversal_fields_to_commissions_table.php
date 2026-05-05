<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commissions', function (Blueprint $table) {
            if (! Schema::hasColumn('commissions', 'is_reversal')) {
                $table->boolean('is_reversal')->default(false)->after('paid_by');
            }
            if (! Schema::hasColumn('commissions', 'original_commission_id')) {
                $table->foreignId('original_commission_id')->nullable()
                    ->after('is_reversal')
                    ->constrained('commissions')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('commissions', function (Blueprint $table) {
            if (Schema::hasColumn('commissions', 'original_commission_id')) {
                $table->dropConstrainedForeignId('original_commission_id');
            }
            $table->dropColumn(['is_reversal']);
        });
    }
};
