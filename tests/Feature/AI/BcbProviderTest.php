<?php

declare(strict_types=1);

use App\Domain\AI\Contracts\DataQuery;
use App\Infrastructure\AI\DataProviders\BcbProvider;

it('getName returns bcb', function () {
    $provider = new BcbProvider;

    expect($provider->getName())->toBe('bcb');
});

it('getCapabilities returns interest_rates, inflation, and exchange_rates', function () {
    $provider = new BcbProvider;

    expect($provider->getCapabilities())->toBe(['interest_rates', 'inflation', 'exchange_rates']);
});

it('query with unsupported capability throws InvalidArgumentException', function () {
    $provider = new BcbProvider;

    $query = new DataQuery(
        capability: 'unsupported_capability',
        params: [],
    );

    $provider->query($query);
})->throws(InvalidArgumentException::class, 'Capability not supported: unsupported_capability');
