<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduler_logs', function (Blueprint $table) {
            $table->id();
            $table->string('job_type', 100)->index();
            $table->enum('status', ['success', 'failed']);
            $table->timestamp('ran_at');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduler_logs');
    }
};
