<?php

namespace App\Application\Handlers;

use App\Application\Commands\ExecutarCompraCommand;
use App\Domain\Basket\Repositories\CestaRepositoryInterface;
use App\Domain\Client\Entities\Custodia;
use App\Domain\Client\Repositories\ClienteRepositoryInterface;
use App\Domain\Client\Repositories\CustodiaRepositoryInterface;
use App\Domain\Client\ValueObjects\Money;
use App\Domain\MarketData\Repositories\CotacaoRepositoryInterface;
use App\Domain\PurchaseEngine\Events\CompraConsolidada;
use App\Domain\PurchaseEngine\Events\CompraDistribuida;
use App\Domain\PurchaseEngine\Repositories\CompraProgramadaRepositoryInterface;
use App\Domain\PurchaseEngine\Repositories\CustodiaMasterRepositoryInterface;
use App\Domain\PurchaseEngine\Services\ConsolidacaoService;
use App\Domain\PurchaseEngine\Services\DistribuicaoService;
use App\Infrastructure\Persistence\Models\CompraDistribuicao;
use App\Infrastructure\Persistence\Models\CompraParticipante;
use App\Infrastructure\Persistence\Models\CompraProgramada;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExecutarCompraHandler
{
    public function __construct(
        private CestaRepositoryInterface $cestaRepository,
        private ClienteRepositoryInterface $clienteRepository,
        private CotacaoRepositoryInterface $cotacaoRepository,
        private CustodiaRepositoryInterface $custodiaRepository,
        private CompraProgramadaRepositoryInterface $compraProgramadaRepository,
        private CustodiaMasterRepositoryInterface $custodiaMasterRepository,
        private ConsolidacaoService $consolidacaoService,
        private DistribuicaoService $distribuicaoService,
    ) {}

    public function handle(ExecutarCompraCommand $command): array
    {
        $dataExecucao = \DateTimeImmutable::createFromFormat('Y-m-d', $command->dataExecucao);

        if ($dataExecucao === false) {
            throw new \InvalidArgumentException('Data inválida. Use formato YYYY-MM-DD');
        }

        // Idempotent: check if already executed
        $existente = $this->compraProgramadaRepository->findByData($dataExecucao);

        if ($existente) {
            throw new \DomainException('COMPRA_JA_EXECUTADA');
        }

        // Get active cesta
        $cesta = $this->cestaRepository->findAtiva();

        if (! $cesta) {
            throw new \DomainException('CESTA_NAO_ENCONTRADA');
        }

        // Get active clients
        $clientes = $this->clienteRepository->findAtivos();

        if (empty($clientes)) {
            throw new \DomainException('NENHUM_CLIENTE_ATIVO');
        }

        // Get latest cotações for cesta tickers
        $cotacoesArray = $this->cotacaoRepository->findLatestByTickers($cesta->tickers());
        $cotacoes = [];

        foreach ($cotacoesArray as $cotacao) {
            $cotacoes[$cotacao->ticker()] = $cotacao;
        }

        if (empty($cotacoes)) {
            throw new \DomainException('COTACOES_NAO_ENCONTRADAS');
        }

        // Get master balances
        $saldosMaster = $this->custodiaMasterRepository->getSaldosByTickers($cesta->tickers());

        // Consolidate
        $consolidacao = $this->consolidacaoService->consolidar($clientes, $cesta, $cotacoes, $saldosMaster);

        // Build prices map
        $precosPorTicker = [];

        foreach ($cotacoes as $ticker => $cotacao) {
            $precosPorTicker[$ticker] = $cotacao->precoFechamento();
        }

        // Distribute
        $distribuicao = $this->distribuicaoService->distribuir(
            $consolidacao->aportesPorCliente,
            $consolidacao->quantidadesDisponiveis,
            $precosPorTicker,
        );

        // Get data_pregao from first cotação
        $dataPregao = ! empty($cotacoes) ? reset($cotacoes)->dataPregao()->format('Y-m-d') : $command->dataExecucao;

        // Persist everything in transaction
        return DB::transaction(function () use ($command, $consolidacao, $distribuicao, $clientes, $precosPorTicker, $dataPregao) {
            // Create CompraProgramada
            $compra = new CompraProgramada;
            $compra->fill([
                'data_execucao' => $command->dataExecucao,
                'status' => 'processando',
                'valor_total' => $consolidacao->valorTotal / 100,
            ]);
            $compra->save();

            // Create CompraParticipante records
            foreach ($consolidacao->aportesPorCliente as $clienteId => $aporte) {
                CompraParticipante::create([
                    'compra_id' => $compra->id,
                    'cliente_id' => $clienteId,
                    'valor_aporte' => $aporte / 100,
                ]);
            }

            // Create CompraDistribuicao records and update custodias
            $clientesById = [];

            foreach ($clientes as $cliente) {
                $clientesById[$cliente->id()] = $cliente;
            }

            foreach ($distribuicao->alocacoes as $alocacao) {
                $preco = $alocacao['preco'];
                $valor = $alocacao['quantidade'] * $preco;
                $tipoLote = $alocacao['quantidade'] >= 100 ? 'padrao' : 'fracionario';

                CompraDistribuicao::create([
                    'compra_id' => $compra->id,
                    'cliente_id' => $alocacao['clienteId'],
                    'ticker' => $alocacao['ticker'],
                    'quantidade' => $alocacao['quantidade'],
                    'valor' => $valor,
                    'preco_unitario' => $preco,
                    'tipo_lote' => $tipoLote,
                    'data_pregao' => $dataPregao,
                ]);

                // Update client custodia — PM recalculation (RN-041/042)
                $custodia = $this->custodiaRepository->findByClienteIdAndTicker(
                    $alocacao['clienteId'],
                    $alocacao['ticker'],
                );

                if (! $custodia) {
                    $custodia = new Custodia(
                        id: (string) Str::uuid(),
                        clienteId: $alocacao['clienteId'],
                        ticker: $alocacao['ticker'],
                    );
                }

                $custodia->adicionarCompra(
                    $alocacao['quantidade'],
                    Money::fromDecimal($preco),
                );

                $this->custodiaRepository->save($custodia);

                // Update valorTotalInvestido
                $cliente = $clientesById[$alocacao['clienteId']] ?? null;

                if ($cliente) {
                    $cliente->adicionarInvestimento(Money::fromDecimal($valor));
                    $this->clienteRepository->save($cliente);
                }

                event(new CompraDistribuida(
                    compraId: $compra->id,
                    clienteId: $alocacao['clienteId'],
                    ticker: $alocacao['ticker'],
                    quantidade: $alocacao['quantidade'],
                    precoUnitario: $preco,
                ));
            }

            // Update master balances with residues (RN-039)
            // First reset all basket tickers to 0
            foreach ($precosPorTicker as $ticker => $preco) {
                $this->custodiaMasterRepository->updateSaldo($ticker, 0);
            }

            // Then set residues
            foreach ($distribuicao->residuos as $ticker => $quantidade) {
                $this->custodiaMasterRepository->updateSaldo($ticker, $quantidade);
            }

            // Mark as completed
            $compra->status = 'concluida';
            $compra->save();

            event(new CompraConsolidada(
                compraId: $compra->id,
                dataExecucao: $compra->data_execucao->format('Y-m-d'),
                valorTotal: (float) $compra->valor_total,
                totalClientes: count($consolidacao->aportesPorCliente),
            ));

            return [
                'compraId' => $compra->id,
                'dataExecucao' => $compra->data_execucao->format('Y-m-d'),
                'valorTotal' => $compra->valor_total,
                'totalClientes' => count($consolidacao->aportesPorCliente),
                'totalDistribuicoes' => count($distribuicao->alocacoes),
                'residuos' => $distribuicao->residuos,
            ];
        });
    }
}
