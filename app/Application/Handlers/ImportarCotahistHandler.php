<?php

namespace App\Application\Handlers;

use App\Application\Commands\ImportarCotahistCommand;
use App\Domain\MarketData\Events\CotacoesImportadas;
use App\Domain\MarketData\Repositories\CotacaoRepositoryInterface;
use App\Domain\MarketData\Services\CotahistParserService;
use App\Infrastructure\B3\CotahistFileReader;

class ImportarCotahistHandler
{
    private const CHUNK_SIZE = 500;

    public function __construct(
        private CotahistParserService $parser,
        private CotacaoRepositoryInterface $cotacaoRepository,
        private CotahistFileReader $fileReader,
    ) {}

    public function handle(ImportarCotahistCommand $command): array
    {
        $totalLines = 0;
        $totalImported = 0;
        $chunk = [];
        $dataPregao = null;

        foreach ($this->fileReader->readLines($command->filePath) as $line) {
            $totalLines++;

            $cotacao = $this->parser->parseLine($line);

            if ($cotacao === null) {
                continue;
            }

            $chunk[] = $cotacao;
            $dataPregao ??= $cotacao->dataPregao()->format('Y-m-d');

            if (count($chunk) >= self::CHUNK_SIZE) {
                $this->cotacaoRepository->saveMany($chunk);
                $totalImported += count($chunk);
                $chunk = [];
            }
        }

        if (! empty($chunk)) {
            $this->cotacaoRepository->saveMany($chunk);
            $totalImported += count($chunk);
        }

        event(new CotacoesImportadas(
            filePath: $command->filePath,
            totalImportadas: $totalImported,
            dataPregao: $dataPregao,
        ));

        return [
            'totalLines' => $totalLines,
            'totalImported' => $totalImported,
            'totalSkipped' => $totalLines - $totalImported,
            'dataPregao' => $dataPregao,
        ];
    }
}
