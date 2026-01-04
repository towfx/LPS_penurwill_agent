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
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('parent_id')->nullable()->default(0);
            $table->string('company_name');
            $table->string('company_registration_number');
            $table->text('company_address');
            $table->string('company_phone');
            $table->string('company_email')->collation('latin1_bin')->unique();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->string('code')->collation('latin1_bin')->unique();
            $table->string('company_profile_file')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partners');
    }
};
