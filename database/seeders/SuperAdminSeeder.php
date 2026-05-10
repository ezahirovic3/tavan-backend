<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'tavanbase@gmail.com'],
            [
                'name'     => 'Tavan Admin',
                'username' => 'tavan_admin',
                'password' => 'changeme123',
                'role'     => 'super_admin',
            ]
        );

        User::updateOrCreate(
            ['email' => 'edib.zahirovic@betastudio.ba'],
            [
                'name'     => 'Edib Zahirović',
                'username' => 'edib_betastudio',
                'password' => 'changeme123',
                'role'     => 'super_admin',
            ]
        );
    }
}
