<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            if (! Schema::hasColumn('agents', 'agent_role')) {
                $table->enum('agent_role', ['agent', 'agent_leader', 'business_partner'])
                    ->default('agent')
                    ->after('status');
            }
            if (! Schema::hasColumn('agents', 'parent_agent_id')) {
                $table->foreignId('parent_agent_id')->nullable()->after('agent_role')
                    ->constrained('agents')->nullOnDelete();
            }
            if (! Schema::hasColumn('agents', 'is_default')) {
                $table->boolean('is_default')->default(false)->after('parent_agent_id');
            }
        });

        Schema::table('agents', function (Blueprint $table) {
            $table->index('agent_role', 'agents_agent_role_index');
            $table->index('is_default', 'agents_is_default_index');
        });
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropIndex('agents_agent_role_index');
            $table->dropIndex('agents_is_default_index');
            if (Schema::hasColumn('agents', 'parent_agent_id')) {
                $table->dropConstrainedForeignId('parent_agent_id');
            }
            $table->dropColumn(['agent_role', 'is_default']);
        });
    }
};
