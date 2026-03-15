<?php

namespace App\Jobs;

use App\Application\Commands\ImportarCotahistCommand;
use App\Application\Handlers\ImportarCotahistHandler;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ImportarCotahistJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public int $tries = 3;

    public function __construct(
        private string $filePath,
    ) {
        $this->onQueue('cotahist-parser');
    }

    public function handle(ImportarCotahistHandler $handler): void
    {
        Log::info("Importando COTAHIST: {$this->filePath}");

        $stats = $handler->handle(new ImportarCotahistCommand(filePath: $this->filePath));

        Log::info('COTAHIST importado', $stats);
    }
}
