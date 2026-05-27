<?php

namespace Database\Seeders;

use App\Models\Holiday;
use App\Models\Quest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create Admin user
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@magangquest.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'onboarding_status' => 'active',
            'intern_type' => 'profesional',
            'start_date' => now()->subYear(),
            'end_date' => now()->addYear(),
            'google_id' => null,
            'avatar' => null,
        ]);

        // Create Mentor users
        $mentor1 = User::create([
            'name' => 'Sarah Mentor',
            'email' => 'mentor@magangquest.com',
            'password' => Hash::make('password123'),
            'role' => 'mentor',
            'onboarding_status' => 'active',
            'intern_type' => 'profesional',
            'start_date' => now()->subMonths(6),
            'end_date' => now()->addMonths(6),
            'google_id' => null,
            'avatar' => null,
        ]);

        $mentor2 = User::create([
            'name' => 'Budi Mentor',
            'email' => 'mentor2@magangquest.com',
            'password' => Hash::make('password123'),
            'role' => 'mentor',
            'onboarding_status' => 'active',
            'intern_type' => 'profesional',
            'start_date' => now()->subMonths(4),
            'end_date' => now()->addMonths(8),
            'google_id' => null,
            'avatar' => null,
        ]);

        // Create Player users
        $player1 = User::create([
            'name' => 'Andi Player',
            'email' => 'player@magangquest.com',
            'password' => Hash::make('password123'),
            'role' => 'player',
            'onboarding_status' => 'active',
            'intern_type' => 'mahasiswa',
            'start_date' => now()->subMonths(3),
            'end_date' => now()->addMonths(3),
            'google_id' => 'google_123456789',
            'avatar' => 'https://ui-avatars.com/api/?name=Andi+Player&background=0D8ABC&color=fff',
        ]);

        $player2 = User::create([
            'name' => 'Siti Player',
            'email' => 'siti@magangquest.com',
            'password' => Hash::make('password123'),
            'role' => 'player',
            'onboarding_status' => 'active',
            'intern_type' => 'sma_smk',
            'start_date' => now()->subMonths(2),
            'end_date' => now()->addMonths(4),
            'google_id' => 'google_987654321',
            'avatar' => 'https://ui-avatars.com/api/?name=Siti+Player&background=FF5733&color=fff',
        ]);

        $player3 = User::create([
            'name' => 'Rudi Player',
            'email' => 'rudi@magangquest.com',
            'password' => Hash::make('password123'),
            'role' => 'player',
            'onboarding_status' => 'pending',
            'intern_type' => 'mahasiswa',
            'start_date' => now()->subMonths(1),
            'end_date' => now()->addMonths(5),
            'google_id' => null,
            'avatar' => null,
        ]);

        // Create sample quests
        $quest1 = Quest::create([
            'title' => 'Introduction to Company Culture',
            'description' => 'Complete the onboarding module about company values and culture. Learn about the history, mission, and vision of the company.',
            'type' => 'assigned',
            'priority' => 'high',
            'slot_weight' => 4,
            'start_date' => now(),
            'due_date' => now()->addDays(7),
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        $quest2 = Quest::create([
            'title' => 'Technical Skills Assessment',
            'description' => 'Complete the technical assessment to evaluate your current skill level. This will help your mentor create a personalized learning plan.',
            'type' => 'assigned',
            'priority' => 'high',
            'slot_weight' => 4,
            'start_date' => now(),
            'due_date' => now()->addDays(14),
            'is_active' => true,
            'created_by' => $mentor1->id,
        ]);

        $quest3 = Quest::create([
            'title' => 'Documentation Review',
            'description' => 'Review and provide feedback on the existing project documentation. Help improve clarity and completeness.',
            'type' => 'bounty',
            'priority' => 'mid',
            'slot_weight' => 2,
            'start_date' => now(),
            'due_date' => now()->addDays(10),
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        $quest4 = Quest::create([
            'title' => 'Code Refactoring Project',
            'description' => 'Propose and implement improvements to the legacy codebase. Focus on readability and performance optimization.',
            'type' => 'bounty',
            'priority' => 'mid',
            'slot_weight' => 2,
            'start_date' => now(),
            'due_date' => now()->addDays(21),
            'is_active' => true,
            'created_by' => $mentor2->id,
        ]);

        $quest5 = Quest::create([
            'title' => 'Weekly Team Standup Notes',
            'description' => 'Document the weekly team standup meeting notes and share with the team.',
            'type' => 'usulan',
            'priority' => 'low',
            'slot_weight' => 1,
            'start_date' => now(),
            'due_date' => now()->addDays(3),
            'is_active' => true,
            'created_by' => $player1->id,
        ]);

        // Create sample holidays
        Holiday::create([
            'date' => now()->month(1)->day(1)->format('Y-m-d'),
            'name' => 'New Year\'s Day',
            'type' => 'national',
            'is_recurring' => true,
        ]);

        Holiday::create([
            'date' => now()->month(8)->day(17)->format('Y-m-d'),
            'name' => 'Independence Day',
            'type' => 'national',
            'is_recurring' => true,
        ]);

        Holiday::create([
            'date' => now()->month(12)->day(25)->format('Y-m-d'),
            'name' => 'Christmas Day',
            'type' => 'national',
            'is_recurring' => true,
        ]);

        Holiday::create([
            'date' => now()->month(5)->day(1)->format('Y-m-d'),
            'name' => 'International Workers\' Day',
            'type' => 'national',
            'is_recurring' => true,
        ]);

        Holiday::create([
            'date' => now()->addDays(7)->format('Y-m-d'),
            'name' => 'Company Anniversary',
            'type' => 'company',
            'is_recurring' => false,
        ]);

        $this->command->info('Database seeded successfully!');
        $this->command->info('Sample Users:');
        $this->command->info('  - Admin: admin@magangquest.com / password123');
        $this->command->info('  - Mentor: mentor@magangquest.com / password123');
        $this->command->info('  - Mentor: mentor2@magangquest.com / password123');
        $this->command->info('  - Player: player@magangquest.com / password123');
        $this->command->info('  - Player: siti@magangquest.com / password123');
        $this->command->info('  - Player: rudi@magangquest.com / password123');
    }
}
