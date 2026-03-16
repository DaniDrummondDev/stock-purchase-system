<?php

declare(strict_types=1);

namespace App\Domain\AI\Contracts;

use App\Domain\AI\ValueObjects\RecommendationResult;

interface RecommendationServiceInterface
{
    public function recommendForCesta(string $cestaId, int $limit = 5): RecommendationResult;
}
