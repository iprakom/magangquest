<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['player', 'mentor', 'admin'])
                  ->default('player')
                  ->after('email');

            $table->enum('onboarding_status', ['restricted', 'pending', 'active', 'frozen'])
                  ->default('restricted')
                  ->after('role');

            $table->enum('intern_type', ['sma_smk', 'mahasiswa', 'profesional'])
                  ->nullable()
                  ->after('onboarding_status');

            $table->date('start_date')->nullable()->after('intern_type');
            $table->date('end_date')->nullable()->after('start_date');
            $table->string('document_path')->nullable()->after('end_date');

            $table->boolean('is_critical_zone')->default(false)->after('document_path');
            $table->boolean('is_grace_period')->default(false)->after('is_critical_zone');
            $table->timestamp('grace_period_started_at')->nullable()->after('is_grace_period');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role',
                'onboarding_status',
                'intern_type',
                'start_date',
                'end_date',
                'document_path',
                'is_critical_zone',
                'is_grace_period',
                'grace_period_started_at',
            ]);
        });
    }
};
