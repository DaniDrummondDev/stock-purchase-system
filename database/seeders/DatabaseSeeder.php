<?php

namespace Database\Seeders;

use App\Application\Commands\AderirClienteCommand;
use App\Application\Handlers\AderirClienteHandler;
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
            ['name' => 'João Silva', 'email' => 'joao@sps.local', 'role' => 'client', 'cpf' => '12345678909', 'valorMensal' => 1000.00],
            ['name' => 'Maria Santos', 'email' => 'maria@sps.local', 'role' => 'client', 'cpf' => '98765432100', 'valorMensal' => 1500.00],
            ['name' => 'Carlos Oliveira', 'email' => 'carlos@sps.local', 'role' => 'client', 'cpf' => '11144477735', 'valorMensal' => 2000.00],
        ];

        $handler = app(AderirClienteHandler::class);

        foreach ($users as $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'role' => $userData['role'],
                    'password' => 'Sps@2026#Secure',
                    'email_verified_at' => now(),
                ],
            );

            if (! $user->hasRole($userData['role'])) {
                $user->assignRole($userData['role']);
            }

            // Create domain Cliente and link to User for client roles
            if ($userData['role'] === 'client' && ! $user->cliente_id) {
                try {
                    $result = $handler->handle(new AderirClienteCommand(
                        nome: $userData['name'],
                        cpf: $userData['cpf'],
                        email: $userData['email'],
                        valorMensal: $userData['valorMensal'],
                    ));

                    $user->update(['cliente_id' => $result['clienteId']]);
                } catch (\DomainException) {
                    // CPF already exists — link existing cliente
                    $cliente = \App\Infrastructure\Persistence\Models\Cliente::where('cpf', $userData['cpf'])->first();
                    if ($cliente && ! $user->cliente_id) {
                        $user->update(['cliente_id' => $cliente->id]);
                    }
                }
            }
        }
    }
}
