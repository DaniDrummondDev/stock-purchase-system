<?php

declare(strict_types=1);

namespace App\Domain\AI\DataProviders;

use App\Domain\AI\Contracts\DataProviderInterface;

final class DataProviderRegistry
{
    /** @var array<string, DataProviderInterface> */
    private array $providers = [];

    /**
     * Register a data provider.
     *
     * @throws \InvalidArgumentException If a provider with the same name is already registered.
     */
    public function register(DataProviderInterface $provider): void
    {
        $name = $provider->getName();

        if (isset($this->providers[$name])) {
            throw new \InvalidArgumentException(
                "Data provider '{$name}' is already registered."
            );
        }

        $this->providers[$name] = $provider;
    }

    /**
     * Get a provider by name.
     *
     * @throws \RuntimeException If the provider is not found.
     */
    public function get(string $name): DataProviderInterface
    {
        if (! isset($this->providers[$name])) {
            throw new \RuntimeException(
                "Data provider '{$name}' is not registered."
            );
        }

        return $this->providers[$name];
    }

    /**
     * @return array<string, DataProviderInterface>
     */
    public function all(): array
    {
        return $this->providers;
    }

    /**
     * Return all providers that declare the given capability.
     *
     * @return array<string, DataProviderInterface>
     */
    public function byCapability(string $capability): array
    {
        return array_filter(
            $this->providers,
            fn (DataProviderInterface $provider): bool => in_array($capability, $provider->getCapabilities(), true),
        );
    }
}
