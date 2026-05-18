<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class MakeSuperAdminCommand extends Command
{
    protected $signature = 'saas:make-super-admin
                            {email : Email super admina}
                            {--password= : Hasło (jeśli tworzymy nowego usera)}
                            {--name=Super Admin : Nazwa (jeśli tworzymy nowego)}';

    protected $description = 'Promuje istniejącego usera na super admina SaaS-a lub tworzy nowego.';

    public function handle(): int
    {
        $email = $this->argument('email');

        $user = User::withoutGlobalScopes()->where('email', $email)->first();

        if (! $user) {
            $password = $this->option('password') ?: $this->secret('Hasło');
            if (! $password || strlen($password) < 8) {
                $this->error('Hasło musi mieć min. 8 znaków.');
                return self::FAILURE;
            }
            $user = User::create([
                'email'             => $email,
                'name'              => $this->option('name'),
                'password'          => Hash::make($password),
                'role'              => 'owner',
                'is_super_admin'    => true,
                'email_verified_at' => now(),
            ]);
            $this->info("Utworzono super admina: {$email}");
        } else {
            $user->update(['is_super_admin' => true]);
            $this->info("Użytkownik {$email} promowany na super admina.");
        }

        return self::SUCCESS;
    }
}
