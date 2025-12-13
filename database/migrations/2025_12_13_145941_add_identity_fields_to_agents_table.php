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
        Schema::table('agents', function (Blueprint $table) {
            $table->string('individual_id_number')->nullable()->after('individual_address');
            $table->string('individual_id_file')->nullable()->after('individual_id_number');
            $table->string('company_reg_file')->nullable()->after('company_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn(['individual_id_number', 'individual_id_file', 'company_reg_file']);
        });
    }
};
