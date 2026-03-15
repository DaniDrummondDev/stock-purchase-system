<?php

namespace App\Application\Handlers;

use App\Application\Commands\ExecutarRebalanceamentoCommand;
use App\Domain\Basket\Repositories\CestaRepositoryInterface;
use App\Domain\Client\Entities\Custodia;
use App\Domain\Client\Repositories\ClienteRepositoryInterface;
use App\Domain\Client\Repositories\CustodiaRepositoryInterface;
use App\Domain\Client\ValueObjects\Money;
use App\Domain\MarketData\Repositories\CotacaoRepositoryInterface;
use App\Domain\Rebalancing\Events\RebalanceamentoTipoB;
use App\Domain\Rebalancing\Services\RebalanceamentoTipoBService;
use App\Domain\Tax\Services\IRVendaService;
use App\Infrastructure\Persistence\Models\OperacaoIR;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExecutarRebalanceamentoHandler
{
    public function __construct(
        private CestaRepositoryInterface $cestaRepository,
        private ClienteRepositoryInterface $clienteRepository,
        private CustodiaRepositoryInterface $custodiaRepository,
        private CotacaoRepositoryInterface $cotacaoRepository,
        private RebalanceamentoTipoBService $tipoBService,
        private IRVendaService $irVendaService,
    ) {}

    public function handle(ExecutarRebalanceamentoCommand $command): array
    {
        $cesta = $this->cestaRepository->findAtiva();

        if (! $cesta) {
            throw new \DomainException('CESTA_NAO_ENCONTRADA');
        }

        $clientes = $command->clienteId
            ? [$this->clienteRepository->findById($command->clienteId)]
            : $this->clienteRepository->findAtivos();

        $clientes = array_filter($clientes);

        if (empty($clientes)) {
            throw new \DomainException('NENHUM_CLIENTE_ATIVO');
        }

        $cotacoesArray = $this->cotacaoRepository->findLatestByTickers($cesta->tickers());
        $cotacoes = [];

        foreach ($cotacoesArray as $cotacao) {
            $cotacoes[$cotacao->ticker()] = $cotacao->precoFechamento();
        }

        $percentuaisAlvo = [];

        foreach ($cesta->ativos() as $ativo) {
            $percentuaisAlvo[$ativo->ticker()->value()] = $ativo->percentual()->value();
        }

        $resultados = [];

        foreach ($clientes as $cliente) {
            $custodias = $this->custodiaRepository->findByClienteId($cliente->id());
            $custodiasMap = [];

            foreach ($custodias as $custodia) {
                $custodiasMap[$custodia->ticker()] = [
                    'quantidade' => $custodia->quantidade(),
                    'precoMedio' => $custodia->precoMedio()->toDecimalString(),
                ];
            }

            $analise = $this->tipoBService->analisar($custodiasMap, $percentuaisAlvo, $cotacoes);

            if (! $analise['necessario']) {
                continue;
            }

            $resultado = DB::transaction(function () use ($cliente, $analise, $custodias) {
                $vendasIR = [];

                // Execute vendas
                foreach ($analise['vendas'] as $venda) {
                    $custodia = $this->findCustodia($custodias, $venda['ticker']);

                    if ($custodia && $custodia->quantidade() >= $venda['quantidade']) {
                        $custodia->removerVenda($venda['quantidade']);
                        $this->custodiaRepository->save($custodia);

                        $vendasIR[] = [
                            'ticker' => $venda['ticker'],
                            'quantidade' => $venda['quantidade'],
                            'precoVenda' => $venda['preco'],
                            'precoMedio' => (float) $venda['precoMedio'],
                        ];
                    }
                }

                // Execute compras
                foreach ($analise['compras'] as $compra) {
                    $custodia = $this->findCustodia($custodias, $compra['ticker']);

                    if (! $custodia) {
                        $custodia = new Custodia(
                            id: (string) Str::uuid(),
                            clienteId: $cliente->id(),
                            ticker: $compra['ticker'],
                        );
                    }

                    $custodia->adicionarCompra($compra['quantidade'], Money::fromDecimal($compra['preco']));
                    $this->custodiaRepository->save($custodia);
                }

                // RN-057 to RN-062: Calculate IR on sales
                $irResult = null;

                if (! empty($vendasIR)) {
                    $irResult = $this->irVendaService->calcular($vendasIR);

                    if ($irResult['valorIR'] > 0) {
                        OperacaoIR::create([
                            'cliente_id' => $cliente->id(),
                            'tipo' => 'venda',
                            'ticker' => 'REBAL',
                            'valor_operacao' => $irResult['totalVendas'],
                            'imposto' => $irResult['valorIR'],
                            'mes_referencia' => now()->format('Y-m'),
                            'publicado_kafka' => false,
                        ]);
                    }
                }

                event(new RebalanceamentoTipoB(
                    clienteId: $cliente->id(),
                    desvios: $analise['desvios'],
                    vendas: $analise['vendas'],
                    compras: $analise['compras'],
                ));

                return [
                    'clienteId' => $cliente->id(),
                    'nome' => $cliente->nome(),
                    'vendas' => count($analise['vendas']),
                    'compras' => count($analise['compras']),
                    'irVenda' => $irResult,
                ];
            });

            $resultados[] = $resultado;
        }

        return [
            'tipo' => 'B',
            'totalClientesAnalisados' => count($clientes),
            'totalClientesRebalanceados' => count($resultados),
            'resultados' => $resultados,
        ];
    }

    /**
     * @param  Custodia[]  $custodias
     */
    private function findCustodia(array $custodias, string $ticker): ?Custodia
    {
        foreach ($custodias as $custodia) {
            if ($custodia->ticker() === strtoupper($ticker)) {
                return $custodia;
            }
        }

        return null;
    }
}
