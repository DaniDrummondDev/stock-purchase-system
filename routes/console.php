<?php

use App\Infrastructure\Jobs\UpdateAtivoEmbeddingsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Update ativo embeddings daily after market close
Schedule::job(UpdateAtivoEmbeddingsJob::class)->dailyAt('19:00');
