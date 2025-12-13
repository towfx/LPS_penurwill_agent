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
        Schema::table('referral_codes', function (Blueprint $table) {
            $table->dropColumn('usage_limit');
        });

        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropColumn('global_referral_usage_limit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('referral_codes', function (Blueprint $table) {
            $table->integer('usage_limit')->nullable();
        });

        Schema::table('system_settings', function (Blueprint $table) {
            $table->integer('global_referral_usage_limit');
        });
    }
};
