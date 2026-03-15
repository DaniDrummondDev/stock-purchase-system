<?php

namespace App\Presentation\Livewire\Dashboard;

use App\Domain\Client\Repositories\ClienteRepositoryInterface;
use App\Domain\Client\Repositories\CustodiaRepositoryInterface;
use App\Domain\MarketData\Repositories\CotacaoRepositoryInterface;
use Livewire\Component;

class ClienteDashboard extends Component
{
    public string $clienteId = '';

    public array $clientes = [];

    public array $carteira = [];

    public float $saldoTotal = 0;

    public float $plTotal = 0;

    public float $valorInvestido = 0;

    public float $rentabilidade = 0;

    public function mount(
        ClienteRepositoryInterface $clienteRepo,
    ): void {
        $clientes = $clienteRepo->findAtivos();

        $this->clientes = array_map(fn ($c) => [
            'id' => $c->id(),
            'nome' => $c->nome(),
        ], $clientes);

        if (! empty($this->clientes)) {
            $this->clienteId = $this->clientes[0]['id'];
            $this->loadCarteira();
        }
    }

    public function updatedClienteId(): void
    {
        $this->loadCarteira();
    }

    public function loadCarteira(): void
    {
        if (empty($this->clienteId)) {
            return;
        }

        $clienteRepo = app(ClienteRepositoryInterface::class);
        $custodiaRepo = app(CustodiaRepositoryInterface::class);
        $cotacaoRepo = app(CotacaoRepositoryInterface::class);

        $cliente = $clienteRepo->findById($this->clienteId);

        if (! $cliente) {
            return;
        }

        $custodias = $custodiaRepo->findByClienteId($this->clienteId);
        $tickers = array_map(fn ($c) => $c->ticker(), $custodias);
        $cotacoes = $cotacaoRepo->findLatestByTickers($tickers);

        $cotacaoMap = [];

        foreach ($cotacoes as $cotacao) {
            $cotacaoMap[$cotacao->ticker()] = $cotacao->precoFechamento();
        }

        $this->carteira = [];
        $this->saldoTotal = 0;
        $this->plTotal = 0;
        $this->valorInvestido = $cliente->valorTotalInvestido()->cents() / 100;

        foreach ($custodias as $custodia) {
            if ($custodia->quantidade() <= 0) {
                continue;
            }

            $cotacaoAtual = $cotacaoMap[$custodia->ticker()] ?? 0;
            $valorAtual = $custodia->quantidade() * $cotacaoAtual;
            $pm = $custodia->precoMedio()->cents() / 100;
            $pl = ($cotacaoAtual - $pm) * $custodia->quantidade();

            $this->carteira[] = [
                'ticker' => $custodia->ticker(),
                'quantidade' => $custodia->quantidade(),
                'precoMedio' => number_format($pm, 2, ',', '.'),
                'cotacaoAtual' => number_format($cotacaoAtual, 2, ',', '.'),
                'valorAtual' => number_format($valorAtual, 2, ',', '.'),
                'pl' => $pl,
                'plFormatted' => ($pl >= 0 ? '+' : '').number_format($pl, 2, ',', '.'),
                'plClass' => $pl >= 0 ? 'text-green-600' : 'text-red-600',
            ];

            $this->saldoTotal += $valorAtual;
            $this->plTotal += $pl;
        }

        // RN-070: Composição percentual real
        foreach ($this->carteira as &$item) {
            $item['composicao'] = $this->saldoTotal > 0
                ? number_format(($item['pl'] + ($item['quantidade'] * ($cotacaoMap[$item['ticker']] ?? 0) - $item['pl'])) / $this->saldoTotal * 100, 1).'%'
                : '0%';
        }

        // Recalcular composição corretamente
        foreach ($this->carteira as &$item) {
            $cotacao = $cotacaoMap[$item['ticker']] ?? 0;
            $valor = $item['quantidade'] * $cotacao;
            $item['composicao'] = $this->saldoTotal > 0
                ? number_format($valor / $this->saldoTotal * 100, 1).'%'
                : '0%';
        }

        // RN-066: Rentabilidade percentual
        $this->rentabilidade = $this->valorInvestido > 0
            ? (($this->saldoTotal - $this->valorInvestido) / $this->valorInvestido) * 100
            : 0;
    }

    public function render()
    {
        return view('livewire.dashboard.cliente-dashboard')
            ->layout('components.layouts.app', ['title' => 'Dashboard']);
    }
}
