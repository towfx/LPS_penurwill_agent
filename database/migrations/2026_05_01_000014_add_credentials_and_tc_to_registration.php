<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->timestamp('tc_accepted_at')->nullable()->after('fee_payment_status');
            $table->timestamp('first_login_at')->nullable()->after('tc_accepted_at');
            $table->string('suspension_reason')->nullable()->after('first_login_at');
            $table->text('rejection_reason')->nullable()->after('suspension_reason');
        });

        // Extend status enum to include rejected and pending (MySQL only — SQLite uses untyped TEXT)
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE agents MODIFY status ENUM('active','inactive','suspended','banned','expired','pending','rejected') DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn(['tc_accepted_at', 'first_login_at', 'suspension_reason', 'rejection_reason']);
        });
    }
};
