<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class SetAdminUser extends Command
{
    protected $signature = 'admin:set {email : The email of the user to make admin}';
    protected $description = 'Set a user as admin by their email';

    public function handle(): int
    {
        $email = $this->argument('email');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email {$email} not found.");
            return Command::FAILURE;
        }

        $user->role = User::ROLE_ADMIN;
        $user->onboarding_status = User::ONBOARDING_ACTIVE;
        $user->save();

        $this->info("User {$email} is now an admin.");
        return Command::SUCCESS;
    }
}
