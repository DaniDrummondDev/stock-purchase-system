<?php

namespace App\Presentation\Livewire\Client;

use App\Application\Commands\AderirClienteCommand;
use App\Application\Handlers\AderirClienteHandler;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AdesaoForm extends Component
{
    public string $nome = '';

    public string $cpf = '';

    public string $email = '';

    public float $valorMensal = 1000;

    public string $message = '';

    public string $messageType = '';

    public bool $jaAderiu = false;

    public function mount(): void
    {
        $user = Auth::user();

        if ($user->cliente_id) {
            $this->jaAderiu = true;

            return;
        }

        // Pre-fill from user data
        $this->nome = $user->name;
        $this->email = $user->email;
    }

    public function aderir(AderirClienteHandler $handler): void
    {
        $this->validate([
            'nome' => 'required|string|max:255',
            'cpf' => 'required|string|size:11',
            'email' => 'required|email|max:255',
            'valorMensal' => 'required|numeric|min:100',
        ]);

        try {
            $result = $handler->handle(new AderirClienteCommand(
                nome: $this->nome,
                cpf: $this->cpf,
                email: $this->email,
                valorMensal: $this->valorMensal,
            ));

            // Link the created Cliente to the logged-in User
            $user = Auth::user();
            $user->cliente_id = $result['clienteId'];
            $user->save();

            $this->jaAderiu = true;
            $this->message = 'Adesão realizada com sucesso! Conta gráfica: '.$result['contaGraficaNumero'];
            $this->messageType = 'success';
        } catch (\DomainException $e) {
            $this->message = match ($e->getMessage()) {
                'CLIENTE_CPF_DUPLICADO' => 'Já existe um cliente cadastrado com este CPF.',
                default => $e->getMessage(),
            };
            $this->messageType = 'error';
        } catch (\InvalidArgumentException $e) {
            $this->message = $e->getMessage();
            $this->messageType = 'error';
        }
    }

    public function render()
    {
        return view('livewire.client.adesao-form')
            ->layout('layouts.app');
    }
}
