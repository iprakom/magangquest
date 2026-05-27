<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('point_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('quest_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('quest_assignment_id')->nullable()->constrained()->onDelete('set null');

            // Transaction type: credit (+) or debit (-)
            $table->enum('type', [
                'credit',    // Positive points
                'debit',     // Negative points
            ]);

            $table->integer('points'); // Can be negative for debits
            $table->integer('balance_after'); // Running balance after this transaction

            $table->string('reference')->nullable(); // e.g., 'onboarding_bonus', 'quest_approved', 'hoarding_penalty'
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'type']);
            $table->index(['reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_transactions');
    }
};
