<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'test@tavan.ba'],
            [
                'name'               => 'Test Korisnik',
                'username'           => 'test_tavan',
                'password'           => Hash::make('test123'),
                'profile_setup_done' => true,
                'email_verified_at'  => now(),
            ]
        );
    }
}
