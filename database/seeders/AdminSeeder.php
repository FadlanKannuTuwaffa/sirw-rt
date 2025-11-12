<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;                    // <-- WAJIB: import model User
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@rt.test'],
            [
                'name'              => 'Administrator RT',
                'username'          => 'admin',
                'password'          => Hash::make('admin123'),
                'role'              => 'admin',
                'email_verified_at' => now(),
                'remember_token'    => Str::random(10),
            ]
        );
    }
}
