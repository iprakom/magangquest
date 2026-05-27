<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('streaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->integer('current_streak')->default(0);
            $table->integer('longest_streak')->default(0);
            $table->date('last_progress_date')->nullable();
            $table->boolean('streak_bonus_claimed')->default(false);

            // Track streak milestones
            $table->boolean('milestone_7')->default(false);
            $table->boolean('milestone_14')->default(false);
            $table->boolean('milestone_21')->default(false);
            $table->boolean('milestone_30')->default(false);

            $table->timestamps();

            $table->unique(['user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('streaks');
    }
};
