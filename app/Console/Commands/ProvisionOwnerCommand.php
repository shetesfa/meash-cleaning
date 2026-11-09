<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ProvisionOwnerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meash:provision-owner
                            {--username=amha : The primary username for the Owner account}
                            {--email= : Real corporate email address for the Owner}
                            {--name=Amha : Full display name of the Owner}
                            {--phone=0911000001 : Corporate contact phone number}
                            {--password= : Optional password provided via secure environment or automation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely provision the initial single Owner account without exposing credentials or duplicating owners';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting Owner provisioning audit...');

        $username = trim($this->option('username') ?: 'amha');

        // Check if a legitimate real Owner already exists (ignore legacy demo owner@meash.com)
        $existingOwner = User::where('username', $username)
            ->orWhere(function ($q) {
                $q->where('role', 'owner')->whereNotIn('email', ['owner@meash.com']);
            })
            ->first();

        if ($existingOwner) {
            $this->warn("Refusing to create duplicate Owner accounts. An active legitimate Owner already exists ({$existingOwner->username} / {$existingOwner->email}).");
            return Command::SUCCESS;
        }

        if (User::where('username', $username)->exists()) {
            $this->error("The username '{$username}' is already in use by another user account.");
            return Command::FAILURE;
        }

        $email = trim($this->option('email') ?: env('MEASH_OWNER_EMAIL', ''));
        if (empty($email)) {
            if ($this->input->isInteractive()) {
                $email = $this->ask('Enter official Owner email address');
            } else {
                $this->error('An official Owner email address is required. Provide via --email or MEASH_OWNER_EMAIL.');
                return Command::FAILURE;
            }
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error("The provided email '{$email}' is not a valid email address.");
            return Command::FAILURE;
        }

        if (User::where('email', $email)->where('username', '!=', $username)->exists()) {
            $this->error("The email address '{$email}' is already registered.");
            return Command::FAILURE;
        }

        $name = trim($this->option('name') ?: 'Amha');
        $phone = trim($this->option('phone') ?: '0911000001');

        $password = $this->option('password') ?: env('MEASH_OWNER_PASSWORD');

        if (empty($password)) {
            if ($this->input->isInteractive()) {
                $password = $this->secret('Enter strong Owner password (minimum 12 characters, mixed case, numbers, symbols)');
                $confirmPassword = $this->secret('Confirm Owner password');

                if ($password !== $confirmPassword) {
                    $this->error('Passwords do not match.');
                    return Command::FAILURE;
                }
            } else {
                $this->error('In non-interactive environments, a password must be provided via --password or MEASH_OWNER_PASSWORD env.');
                return Command::FAILURE;
            }
        }

        if (strlen($password) < 12 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $this->error('The Owner password must be at least 12 characters long and include uppercase letters, lowercase letters, and numbers.');
            return Command::FAILURE;
        }

        // Create the Owner securely
        $owner = User::create([
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'phone' => $phone,
            'role' => 'owner',
            'password' => Hash::make($password),
            'is_active' => true,
        ]);

        $this->info("Initial Owner account successfully provisioned for '{$username}' ({$email}).");
        $this->info("System is ready for Owner authentication via username, email, or phone.");

        return Command::SUCCESS;
    }
}
