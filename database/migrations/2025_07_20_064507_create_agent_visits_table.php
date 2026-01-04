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
        Schema::create('agent_visits', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('agent_id');
            $table->string('referral_code', 50)->collation('latin1_bin');
            $table->string('visit_url', 500);
            $table->timestamp('visit_time');
            $table->string('referral_page', 255)->nullable();
            $table->string('session_id', 100)->collation('latin1_bin')->nullable();
            $table->string('page_title', 255)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('screen_resolution', 50)->nullable();
            $table->string('language', 10)->nullable();
            $table->string('timezone', 50)->nullable();
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['agent_id', 'visit_time']);
            $table->index('referral_code');
            $table->index('session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_visits');
    }
};
