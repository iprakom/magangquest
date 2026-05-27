<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, integer, boolean, json
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insert default values
        DB::table('system_settings')->insert([
            ['key' => 'global_limit', 'value' => '4', 'type' => 'integer', 'description' => 'Maximum workload slots for interns (WIP Limit)'],
            ['key' => 'slot_multiplier', 'value' => '4', 'type' => 'integer', 'description' => 'Multiplier to convert Global Limit to max capacity slots'],
            ['key' => 'critical_zone_days', 'value' => '10', 'type' => 'integer', 'description' => 'Days before end date to trigger Critical Zone'],
            ['key' => 'grace_period_days', 'value' => '7', 'type' => 'integer', 'description' => 'Days after end date for Grace Period'],
            ['key' => 'force_close_days', 'value' => '8', 'type' => 'integer', 'description' => 'Days after end date to force close'],
            ['key' => 'sla_hours', 'value' => '72', 'type' => 'integer', 'description' => 'SLA for mentor validation in hours (3x24hr)'],
            ['key' => 'hoarding_days', 'value' => '10', 'type' => 'integer', 'description' => 'Days before task is considered hoarding'],
            ['key' => 'onboarding_bonus', 'value' => '50', 'type' => 'integer', 'description' => 'Points awarded for completing onboarding'],
            ['key' => 'graduation_bonus', 'value' => '200', 'type' => 'integer', 'description' => 'Points awarded for perfect graduation'],
            ['key' => 'late_penalty_per_day', 'value' => '10', 'type' => 'integer', 'description' => 'Points deducted per day in Grace Period'],
            ['key' => 'hoarding_penalty', 'value' => '50', 'type' => 'integer', 'description' => 'Points deducted for hoarding/expired task'],
            ['key' => 'revise_penalty', 'value' => '10', 'type' => 'integer', 'description' => 'Points deducted for revise'],
            ['key' => 'cancel_penalty', 'value' => '10', 'type' => 'integer', 'description' => 'Points deducted for cancelling'],
            ['key' => 'quest_approved_points', 'value' => '100', 'type' => 'integer', 'description' => 'Points awarded when quest is approved'],
            ['key' => 'progress_points', 'value' => '10', 'type' => 'integer', 'description' => 'Points awarded for daily progress'],
            ['key' => 'usulan_approved_points', 'value' => '20', 'type' => 'integer', 'description' => 'Points awarded when usulan is approved'],
            ['key' => 'streak_7_bonus', 'value' => '50', 'type' => 'integer', 'description' => 'Streak bonus at 7 days'],
            ['key' => 'streak_14_bonus', 'value' => '100', 'type' => 'integer', 'description' => 'Streak bonus at 14 days'],
            ['key' => 'streak_21_bonus', 'value' => '200', 'type' => 'integer', 'description' => 'Streak bonus at 21 days'],
            ['key' => 'streak_30_bonus', 'value' => '500', 'type' => 'integer', 'description' => 'Streak bonus at 30 days'],
            ['key' => 'force_close_penalty', 'value' => '50', 'type' => 'integer', 'description' => 'Penalty at force close'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
