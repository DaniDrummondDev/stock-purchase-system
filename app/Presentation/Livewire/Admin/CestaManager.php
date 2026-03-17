<?php

namespace App\Presentation\Livewire\Admin;

use App\Application\Commands\AlterarCestaCommand;
use App\Application\Commands\CriarCestaCommand;
use App\Application\Handlers\AlterarCestaHandler;
use App\Application\Handlers\CriarCestaHandler;
use App\Domain\Basket\Repositories\CestaRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class CestaManager extends Component
{
    public string $nome = '';

    public array $ativos = [];

    public ?array $cestaAtual = null;

    public array $historico = [];

    public string $message = '';

    public string $messageType = '';

    public function mount(CestaRepositoryInterface $cestaRepo): void
    {
        abort_unless(Auth::user()?->hasRole(['admin', 'analyst', 'auditor']), 403);

        $this->resetAtivos();
        $this->loadData($cestaRepo);
    }

    public function save(
        CestaRepositoryInterface $cestaRepo,
        CriarCestaHandler $criarHandler,
        AlterarCestaHandler $alterarHandler,
    ): void {
        $this->validate([
            'nome' => 'required|string|max:255',
            'ativos.*.ticker' => 'required|string|max:12',
            'ativos.*.percentual' => 'required|numeric|gt:0|max:100',
        ]);

        try {
            $ativosData = array_map(fn ($a) => [
                'ticker' => strtoupper($a['ticker']),
                'percentual' => (float) $a['percentual'],
            ], $this->ativos);

            $cestaAtiva = $cestaRepo->findAtiva();

            if ($cestaAtiva) {
                $alterarHandler->handle(new AlterarCestaCommand(nome: $this->nome, ativos: $ativosData));
                $this->message = 'Cesta atualizada com sucesso!';
            } else {
                $criarHandler->handle(new CriarCestaCommand(nome: $this->nome, ativos: $ativosData));
                $this->message = 'Cesta criada com sucesso!';
            }

            $this->messageType = 'success';
            $this->loadData($cestaRepo);
        } catch (\Exception $e) {
            $this->message = $e->getMessage();
            $this->messageType = 'error';
        }
    }

    private function loadData(CestaRepositoryInterface $cestaRepo): void
    {
        $cesta = $cestaRepo->findAtiva();

        if ($cesta) {
            $this->cestaAtual = [
                'id' => $cesta->id(),
                'nome' => $cesta->nome(),
                'ativos' => array_map(fn ($a) => [
                    'ticker' => $a->ticker()->value(),
                    'percentual' => $a->percentual()->toDecimalString(),
                ], $cesta->ativos()),
            ];

            $this->nome = $cesta->nome();
            $this->ativos = array_map(fn ($a) => [
                'ticker' => $a->ticker()->value(),
                'percentual' => $a->percentual()->value(),
            ], $cesta->ativos());
        }

        $all = $cestaRepo->findAll();
        $this->historico = array_map(fn ($c) => [
            'id' => $c->id(),
            'nome' => $c->nome(),
            'ativo' => $c->isAtiva(),
            'ativos' => array_map(fn ($a) => $a->ticker()->value().' ('.$a->percentual()->toDecimalString().'%)', $c->ativos()),
            'data' => $c->createdAt()->format('d/m/Y H:i'),
        ], $all);
    }

    #[On('cesta-suggestion-applied')]
    public function applySuggestion(array $ativos): void
    {
        $this->ativos = array_slice(array_map(fn ($a) => [
            'ticker' => strtoupper($a['ticker']),
            'percentual' => (float) $a['percentual'],
        ], $ativos), 0, 5);

        $this->message = 'Sugestão IA aplicada. Revise e salve.';
        $this->messageType = 'success';
    }

    private function resetAtivos(): void
    {
        $this->ativos = array_fill(0, 5, ['ticker' => '', 'percentual' => '']);
    }

    public function render()
    {
        return view('livewire.admin.cesta-manager')
            ->layout('layouts.app');
    }
}
