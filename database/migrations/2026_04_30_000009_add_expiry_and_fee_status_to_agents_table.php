<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            if (! Schema::hasColumn('agents', 'registered_at')) {
                $table->date('registered_at')->nullable()->after('status');
            }
            if (! Schema::hasColumn('agents', 'expires_at')) {
                $table->date('expires_at')->nullable()->after('registered_at');
            }
            if (! Schema::hasColumn('agents', 'renewal_due_at')) {
                $table->date('renewal_due_at')->nullable()->after('expires_at');
            }
            if (! Schema::hasColumn('agents', 'fee_payment_status')) {
                $table->enum('fee_payment_status', ['pending', 'paid', 'overdue', 'waived'])
                    ->default('pending')
                    ->after('renewal_due_at');
            }
        });

        // Extend status enum to add 'expired' (MySQL only — SQLite stores as TEXT, no constraint).
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE agents MODIFY status ENUM('active','inactive','suspended','banned','expired') DEFAULT 'active'");
        }
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn(['registered_at', 'expires_at', 'renewal_due_at', 'fee_payment_status']);
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE agents MODIFY status ENUM('active','inactive','suspended','banned') DEFAULT 'active'");
        }
    }
};
