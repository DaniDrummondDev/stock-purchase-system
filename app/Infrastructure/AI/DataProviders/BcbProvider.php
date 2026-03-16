<?php

declare(strict_types=1);

namespace App\Infrastructure\AI\DataProviders;

use App\Domain\AI\Contracts\DataProviderInterface;
use App\Domain\AI\Contracts\DataQuery;
use App\Domain\AI\Contracts\DataResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class BcbProvider implements DataProviderInterface
{
    private const BASE_URL = 'https://api.bcb.gov.br/dados/serie/bcdata.sgs.';

    private const SERIES = [
        'selic' => 432,
        'ipca' => 433,
        'usd_brl' => 1,
    ];

    public function getName(): string
    {
        return 'bcb';
    }

    public function getCapabilities(): array
    {
        return ['interest_rates', 'inflation', 'exchange_rates'];
    }

    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(5)->get(self::BASE_URL.'432/dados/ultimos/1?formato=json');

            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    public function query(DataQuery $query): DataResult
    {
        $data = match ($query->capability) {
            'interest_rates' => $this->fetchSeries('selic'),
            'inflation' => $this->fetchSeries('ipca'),
            'exchange_rates' => $this->fetchSeries('usd_brl'),
            default => throw new \InvalidArgumentException("Capability not supported: {$query->capability}"),
        };

        return new DataResult(
            data: $data,
            providerName: $this->getName(),
            fromCache: false,
            fetchedAt: new \DateTimeImmutable,
        );
    }

    private function fetchSeries(string $key): array
    {
        $serieId = self::SERIES[$key] ?? throw new \InvalidArgumentException("Unknown series: {$key}");

        $maxRetries = 3;
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $response = Http::timeout(10)
                    ->retry(0) // we handle retries manually
                    ->get(self::BASE_URL."{$serieId}/dados/ultimos/5?formato=json");

                if ($response->successful()) {
                    $records = $response->json();

                    if (empty($records)) {
                        return ['serie' => $key, 'valor' => null, 'data' => null];
                    }

                    $latest = end($records);

                    return [
                        'serie' => $key,
                        'serie_id' => $serieId,
                        'valor' => (float) str_replace(',', '.', $latest['valor']),
                        'data' => $latest['data'],
                        'historico' => array_map(fn ($r) => [
                            'valor' => (float) str_replace(',', '.', $r['valor']),
                            'data' => $r['data'],
                        ], $records),
                    ];
                }

                throw new \RuntimeException("BCB API returned status {$response->status()}");
            } catch (\Throwable $e) {
                $lastException = $e;
                Log::warning("BcbProvider: Attempt {$attempt}/{$maxRetries} failed for {$key}: {$e->getMessage()}");

                if ($attempt < $maxRetries) {
                    // Exponential backoff: 1s, 2s, 4s
                    usleep((int) (pow(2, $attempt - 1) * 1_000_000));
                }
            }
        }

        throw new \RuntimeException("BcbProvider: All {$maxRetries} attempts failed for {$key}", 0, $lastException);
    }
}
