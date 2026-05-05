<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            if (! Schema::hasColumn('agents', 'company_representative_id_file')) {
                $afterColumn = Schema::hasColumn('agents', 'company_reg_file') ? 'company_reg_file' : 'company_phone';
                $table->string('company_representative_id_file')->nullable()->after($afterColumn);
            }
        });
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn('company_representative_id_file');
        });
    }
};
