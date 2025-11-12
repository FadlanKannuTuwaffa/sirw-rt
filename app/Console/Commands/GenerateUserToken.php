<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class GenerateUserToken extends Command
{
    protected $signature = 'user:token {email}';
    protected $description = 'Generate API token for user';

    public function handle(): int
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User dengan email {$email} tidak ditemukan.");
            return 1;
        }

        $token = $user->createToken('assistant')->plainTextToken;

        $this->info("Token untuk {$user->name}:");
        $this->line($token);
        $this->newLine();
        $this->info("Simpan token ini di localStorage browser dengan key 'api_token'");
        $this->info("Atau jalankan di console browser:");
        $this->line("localStorage.setItem('api_token', '{$token}')");

        return 0;
    }
}
