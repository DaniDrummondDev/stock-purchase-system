<?php

namespace App\Presentation\Livewire\Admin;

use App\Infrastructure\Persistence\Models\CustodiaMaster;
use Livewire\Component;

class ContaMasterPanel extends Component
{
    public array $saldos = [];

    public function mount(): void
    {
        $this->saldos = CustodiaMaster::orderBy('ticker')
            ->get()
            ->map(fn ($m) => [
                'ticker' => $m->ticker,
                'quantidade' => $m->quantidade,
                'precoMedio' => number_format((float) $m->preco_medio, 2, ',', '.'),
            ])
            ->all();
    }

    public function render()
    {
        return view('livewire.admin.conta-master-panel')
            ->layout('components.layouts.app', ['title' => 'Conta Master']);
    }
}
