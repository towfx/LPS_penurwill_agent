<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->enum('fee_type', ['entry', 'renewal']);
            $table->string('role', 50);
            $table->decimal('amount', 10, 2);
            $table->enum('payment_method', ['stripe', 'bank_transfer', 'manual', 'waived'])->default('manual');
            $table->string('payment_reference', 255)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['agent_id', 'fee_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_payments');
    }
};
