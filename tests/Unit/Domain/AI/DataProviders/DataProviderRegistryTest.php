<?php

declare(strict_types=1);

use App\Domain\AI\Contracts\DataProviderInterface;
use App\Domain\AI\Contracts\DataQuery;
use App\Domain\AI\Contracts\DataResult;
use App\Domain\AI\DataProviders\DataProviderRegistry;

function createMockProvider(string $name, array $capabilities): DataProviderInterface
{
    return new class($name, $capabilities) implements DataProviderInterface
    {
        public function __construct(
            private readonly string $name,
            private readonly array $capabilities,
        ) {}

        public function getName(): string
        {
            return $this->name;
        }

        public function getCapabilities(): array
        {
            return $this->capabilities;
        }

        public function isAvailable(): bool
        {
            return true;
        }

        public function query(DataQuery $query): DataResult
        {
            return new DataResult(
                data: [],
                providerName: $this->name,
                fromCache: false,
                fetchedAt: new DateTimeImmutable,
            );
        }
    };
}

it('registers and retrieves a provider', function () {
    $registry = new DataProviderRegistry;
    $provider = createMockProvider('cotahist', ['quotation']);

    $registry->register($provider);

    expect($registry->get('cotahist'))->toBe($provider);
});

it('returns all registered providers', function () {
    $registry = new DataProviderRegistry;
    $registry->register(createMockProvider('cotahist', ['quotation']));
    $registry->register(createMockProvider('bcb', ['macro_indicators']));

    expect($registry->all())->toHaveCount(2);
});

it('finds providers by capability', function () {
    $registry = new DataProviderRegistry;
    $registry->register(createMockProvider('cotahist', ['quotation', 'volume']));
    $registry->register(createMockProvider('bcb', ['macro_indicators']));

    $providers = $registry->byCapability('quotation');

    expect($providers)->toHaveCount(1)
        ->and(array_values($providers)[0]->getName())->toBe('cotahist');
});

it('throws when provider name is already registered', function () {
    $registry = new DataProviderRegistry;
    $registry->register(createMockProvider('cotahist', ['quotation']));

    $registry->register(createMockProvider('cotahist', ['volume']));
})->throws(InvalidArgumentException::class);

it('throws when getting unknown provider', function () {
    $registry = new DataProviderRegistry;

    $registry->get('unknown');
})->throws(RuntimeException::class);

it('returns empty array for unknown capability', function () {
    $registry = new DataProviderRegistry;
    $registry->register(createMockProvider('cotahist', ['quotation']));

    expect($registry->byCapability('unknown'))->toBe([]);
});
