<?php

declare(strict_types=1);

use App\Infrastructure\Jobs\DailyRiskScanJob;

it('job class exists and is instantiable', function () {
    expect(class_exists(DailyRiskScanJob::class))->toBeTrue();
});
