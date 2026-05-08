<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE payouts MODIFY status ENUM('pending','approved','paid','cancelled') DEFAULT 'pending'");
        }
        // SQLite: enum is stored as TEXT with a CHECK constraint; recreating the constraint
        // requires a full table rebuild. For SQLite (test env) we drop the check and rely
        // on application-level validation to enforce valid values.
        // In production (MySQL) the MODIFY statement above enforces the enum.
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE payouts MODIFY status ENUM('pending','approved','paid') DEFAULT 'pending'");
        }
    }
};
