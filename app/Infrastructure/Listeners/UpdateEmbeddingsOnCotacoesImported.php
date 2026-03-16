<?php

namespace App\Infrastructure\Listeners;

use App\Domain\MarketData\Events\CotacoesImportadas;
use App\Infrastructure\Jobs\UpdateAtivoEmbeddingsJob;

class UpdateEmbeddingsOnCotacoesImported
{
    public function handle(CotacoesImportadas $event): void
    {
        UpdateAtivoEmbeddingsJob::dispatch($event->dataPregao)
            ->delay(now()->addSeconds(60));
    }
}
