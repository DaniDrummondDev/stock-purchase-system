<?php

namespace App\Application\Handlers;

use App\Domain\Tax\Events\IRDedoDuroCalculado;
use App\Domain\Tax\Services\DedoDuroService;
use App\Infrastructure\Kafka\KafkaProducer;
use App\Infrastructure\Kafka\Messages\IRDedoDuroMessage;
use App\Infrastructure\Persistence\Models\OperacaoIR;
use Illuminate\Support\Facades\Log;

class PublicarIRDedoDuroHandler
{
    public function __construct(
        private DedoDuroService $dedoDuroService,
        private KafkaProducer $kafkaProducer,
    ) {}

    /**
     * Calcula IR dedo-duro para uma distribuição e publica no Kafka.
     */
    public function handle(
        string $clienteId,
        string $cpf,
        string $ticker,
        int $quantidade,
        float $precoUnitario,
        string $dataOperacao,
    ): float {
        $valorOperacao = $quantidade * $precoUnitario;
        $valorIR = $this->dedoDuroService->calcular($valorOperacao);

        // Save to DB
        $operacao = OperacaoIR::create([
            'cliente_id' => $clienteId,
            'tipo' => 'dedo_duro',
            'ticker' => $ticker,
            'valor_operacao' => $valorOperacao,
            'imposto' => $valorIR,
            'mes_referencia' => substr($dataOperacao, 0, 7),
            'publicado_kafka' => false,
        ]);

        // Publish to Kafka
        $event = new IRDedoDuroCalculado(
            clienteId: $clienteId,
            cpf: $cpf,
            ticker: $ticker,
            quantidade: $quantidade,
            precoUnitario: $precoUnitario,
            valorOperacao: $valorOperacao,
            valorIR: $valorIR,
            dataOperacao: $dataOperacao,
        );

        $message = IRDedoDuroMessage::fromEvent($event);
        $topic = config('kafka.topics.ir_dedo_duro');

        $published = $this->kafkaProducer->produce($topic, $message);

        if ($published) {
            $operacao->update(['publicado_kafka' => true]);
        } else {
            Log::warning('Failed to publish IR dedo-duro to Kafka', ['operacao_id' => $operacao->id]);
        }

        event($event);

        return $valorIR;
    }
}
