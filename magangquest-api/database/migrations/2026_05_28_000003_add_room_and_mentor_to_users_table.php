<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('room', 100)->nullable()->after('end_date');
            $table->foreignId('mentor_id')->nullable()->constrained('users')->nullOnDelete()->after('room');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['mentor_id']);
            $table->dropColumn(['room', 'mentor_id']);
        });
    }
};
