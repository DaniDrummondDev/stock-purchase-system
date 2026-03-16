<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $users = [
            ['name' => 'Admin SPS', 'email' => 'admin@sps.local', 'role' => 'admin'],
            ['name' => 'Ana Analyst', 'email' => 'analyst@sps.local', 'role' => 'analyst'],
            ['name' => 'Auditor SPS', 'email' => 'auditor@sps.local', 'role' => 'auditor'],
            ['name' => 'João Silva', 'email' => 'joao@sps.local', 'role' => 'client'],
            ['name' => 'Maria Santos', 'email' => 'maria@sps.local', 'role' => 'client'],
            ['name' => 'Carlos Oliveira', 'email' => 'carlos@sps.local', 'role' => 'client'],
        ];

        foreach ($users as $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    ...$userData,
                    'password' => 'Sps@2026#Secure',
                    'email_verified_at' => now(),
                ],
            );

            if (! $user->hasRole($userData['role'])) {
                $user->assignRole($userData['role']);
            }
        }
    }
}
