<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@tavan.store'],
            [
                'name'              => 'Tavan Admin',
                'username'          => 'admin',
                'password'          => Hash::make('password'),
                'email_verified_at' => now(),
                'is_verified'       => true,
            ]
        );

        $this->command->info('Admin user ready: admin@tavan.store / password');
    }
}
