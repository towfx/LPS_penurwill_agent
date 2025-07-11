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
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->string('individual_name')->nullable();
            $table->string('individual_phone')->nullable();
            $table->text('individual_address')->nullable();
            $table->string('company_representative_name')->nullable();
            $table->string('company_name')->nullable();
            $table->string('company_registration_number')->nullable();
            $table->text('company_address')->nullable();
            $table->string('company_phone')->nullable();
            $table->enum('profile_type', ['individual', 'company']);
            $table->foreignId('referral_code_id')->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended', 'banned'])->default('active');
            $table->string('profile_image')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
