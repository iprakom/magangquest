<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quest_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quest_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('validated_by')->nullable()->constrained('users')->onDelete('set null');

            // Status: open, assigned, active, paused, in_review, approved, revise, cancelled, failed
            $table->enum('status', [
                'open',        // Available in bounty
                'assigned',    // Locked to user, ready to start
                'active',      // In progress
                'paused',      // Temporarily suspended
                'in_review',   // Submitted, waiting mentor validation
                'approved',    // Completed successfully
                'revise',      // Returned with notes
                'cancelled',   // Cancelled by user
                'failed',      // Hoarding/expired
            ])->default('open');

            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('validated_at')->nullable();

            $table->text('mentor_notes')->nullable();
            $table->timestamps();

            // Slot consumption tracking
            $table->integer('slot_consumed')->default(0);

            $table->index(['user_id', 'status']);
            $table->index(['quest_id', 'user_id']);
            $table->unique(['quest_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quest_assignments');
    }
};
