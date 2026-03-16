<?php

namespace App\Presentation\Livewire\Admin;

use App\Domain\PurchaseEngine\Repositories\CompraProgramadaRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ComprasPanel extends Component
{
    public array $compras = [];

    public function mount(CompraProgramadaRepositoryInterface $repo): void
    {
        abort_unless(Auth::user()?->hasRole(['admin', 'analyst', 'auditor']), 403);

        $all = $repo->findAll();

        $this->compras = array_map(fn ($c) => [
            'id' => $c->id,
            'dataExecucao' => $c->data_execucao->format('d/m/Y'),
            'status' => $c->status,
            'valorTotal' => number_format((float) $c->valor_total, 2, ',', '.'),
            'participantes' => $c->participantes->count(),
            'distribuicoes' => $c->distribuicoes->count(),
        ], $all);
    }

    public function render()
    {
        return view('livewire.admin.compras-panel')
            ->layout('layouts.app');
    }
}
