<?php

namespace App\Infrastructure\AI;

use App\Infrastructure\Persistence\Models\AiConfiguration;

class AiConfigResolver
{
    /**
     * Resolve the AI provider config for a given purpose.
     *
     * Priority: user config → global config → .env fallback
     */
    public function resolve(string $purpose, ?string $userId = null): array
    {
        if ($userId) {
            $userConfig = AiConfiguration::forUser($userId)
                ->forPurpose($purpose)
                ->active()
                ->first();

            if ($userConfig) {
                return $this->toProviderConfig($userConfig);
            }
        }

        $globalConfig = AiConfiguration::global()
            ->forPurpose($purpose)
            ->active()
            ->first();

        if ($globalConfig) {
            return $this->toProviderConfig($globalConfig);
        }

        return $this->envFallback($purpose);
    }

    /**
     * Get the provider name for runtime config override.
     */
    public function resolveProviderName(string $purpose, ?string $userId = null): string
    {
        $config = $this->resolve($purpose, $userId);

        return $config['provider'];
    }

    private function toProviderConfig(AiConfiguration $config): array
    {
        return [
            'provider' => $config->provider,
            'api_key' => $config->api_key,
            'settings' => $config->settings,
            'source' => $config->scope,
        ];
    }

    private function envFallback(string $purpose): array
    {
        $defaultKey = match ($purpose) {
            'llm' => 'AI_DEFAULT_PROVIDER',
            'embeddings' => 'AI_DEFAULT_EMBEDDINGS_PROVIDER',
            default => 'AI_DEFAULT_PROVIDER',
        };

        $provider = config("ai.default_for_{$purpose}", config('ai.default', 'anthropic'));

        return [
            'provider' => $provider,
            'api_key' => config("ai.providers.{$provider}.key", ''),
            'settings' => [],
            'source' => 'env',
        ];
    }
}
