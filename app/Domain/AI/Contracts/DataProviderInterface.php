<?php

declare(strict_types=1);

namespace App\Domain\AI\Contracts;

interface DataProviderInterface
{
    public function getName(): string;

    /**
     * @return array<int, string>
     */
    public function getCapabilities(): array;

    public function isAvailable(): bool;

    public function query(DataQuery $query): DataResult;
}
