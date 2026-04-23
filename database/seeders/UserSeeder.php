<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'administrateur@samabois.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('samabois'),
                'role' => 'admin',
            ]
        );

        User::updateOrCreate(
            ['email' => 'comptable@samabois.com'],
            [
                'name' => 'Comptable',
                'password' => Hash::make('compta2026'),
                'role' => 'comptable',
            ]
        );
    }
}
