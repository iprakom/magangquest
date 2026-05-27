<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quest_assignments', function (Blueprint $table) {
            $table->timestamp('sla_deadline')->nullable()->after('mentor_notes');
            $table->index(['status', 'sla_deadline']);
        });
    }

    public function down(): void
    {
        Schema::table('quest_assignments', function (Blueprint $table) {
            $table->dropIndex(['status', 'sla_deadline']);
            $table->dropColumn('sla_deadline');
        });
    }
};
