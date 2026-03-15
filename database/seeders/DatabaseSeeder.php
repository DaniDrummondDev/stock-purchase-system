<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin SPS',
                'email' => 'admin@sps.local',
                'role' => 'admin',
            ],
            [
                'name' => 'Ana Analyst',
                'email' => 'analyst@sps.local',
                'role' => 'analyst',
            ],
            [
                'name' => 'Auditor SPS',
                'email' => 'auditor@sps.local',
                'role' => 'auditor',
            ],
            [
                'name' => 'João Silva',
                'email' => 'joao@sps.local',
                'role' => 'client',
            ],
            [
                'name' => 'Maria Santos',
                'email' => 'maria@sps.local',
                'role' => 'client',
            ],
            [
                'name' => 'Carlos Oliveira',
                'email' => 'carlos@sps.local',
                'role' => 'client',
            ],
        ];

        foreach ($users as $userData) {
            User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    ...$userData,
                    'password' => 'password',
                    'email_verified_at' => now(),
                ],
            );
        }
    }
}
