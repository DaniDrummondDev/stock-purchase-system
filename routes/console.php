<?php

use App\Infrastructure\Jobs\DailyRiskScanJob;
use App\Infrastructure\Jobs\MarketBriefingJob;
use App\Infrastructure\Jobs\RebalancingCheckJob;
use App\Infrastructure\Jobs\TaxAlertMonthlyJob;
use App\Infrastructure\Jobs\UpdateAtivoEmbeddingsJob;
use App\Infrastructure\Jobs\WeeklyPortfolioReportJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Update ativo embeddings daily after market close
Schedule::job(UpdateAtivoEmbeddingsJob::class)->dailyAt('19:00');

// Agent-driven scheduled jobs (Sprint 10)
Schedule::job(DailyRiskScanJob::class)->dailyAt('20:00');
Schedule::job(MarketBriefingJob::class)->dailyAt('09:00');
Schedule::job(WeeklyPortfolioReportJob::class)->weeklyOn(5, '18:00'); // Friday
Schedule::job(RebalancingCheckJob::class)->dailyAt('20:30');
Schedule::job(TaxAlertMonthlyJob::class)->monthlyOn(1, '09:00');
