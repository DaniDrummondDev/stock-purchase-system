<?php

namespace App\Presentation\Livewire\Admin;

use App\Infrastructure\Persistence\Models\AiConfiguration;
use Illuminate\Support\Facades\Auth;
use Laravel\Ai\Ai;
use Livewire\Component;

class AiConfigPanel extends Component
{
    public array $configs = [];

    public string $message = '';

    public string $messageType = '';

    public string $testingPurpose = '';

    public function mount(): void
    {
        abort_unless(Auth::user()?->hasRole('admin'), 403);

        $this->loadConfigs();
    }

    public function save(string $purpose): void
    {
        $data = $this->configs[$purpose] ?? null;
        if (! $data) {
            return;
        }

        $this->validate([
            "configs.{$purpose}.provider" => 'required|string',
            "configs.{$purpose}.api_key" => 'required|string|min:5',
        ]);

        $config = AiConfiguration::global()
            ->forPurpose($purpose)
            ->first();

        $attributes = [
            'scope' => 'global',
            'purpose' => $purpose,
            'provider' => $data['provider'],
            'api_key' => $data['api_key'],
            'settings' => array_filter([
                'model' => $data['model'] ?? null,
                'base_url' => $data['base_url'] ?? null,
            ]),
            'is_active' => $data['is_active'] ?? true,
        ];

        if ($config) {
            $config->update($attributes);
        } else {
            AiConfiguration::create($attributes);
        }

        $this->message = ucfirst($purpose).' — configuração salva com sucesso!';
        $this->messageType = 'success';
        $this->loadConfigs();
    }

    public function testConnection(string $purpose): void
    {
        $this->testingPurpose = $purpose;
        $data = $this->configs[$purpose] ?? null;

        if (! $data || empty($data['api_key'])) {
            $this->message = 'Preencha a API key antes de testar.';
            $this->messageType = 'error';
            $this->testingPurpose = '';

            return;
        }

        try {
            // Temporarily set the API key in config for testing
            $provider = $data['provider'];
            $originalKey = config("ai.providers.{$provider}.key");
            config(["ai.providers.{$provider}.key" => $data['api_key']]);

            if ($data['base_url'] ?? null) {
                config(["ai.providers.{$provider}.url" => $data['base_url']]);
            }

            if ($data['model'] ?? null) {
                config(["ai.providers.{$provider}.model" => $data['model']]);
            }

            if ($purpose === 'embeddings') {
                Ai::embeddingProvider($provider)->embeddings()->create('test connection');
            } else {
                $agent = new \Laravel\Ai\AnonymousAgent(
                    instructions: 'Test connection',
                    messages: [],
                    tools: [],
                );
                $agent->prompt('Respond with OK', provider: $provider);
            }

            // Restore original key
            config(["ai.providers.{$provider}.key" => $originalKey]);

            // Mark as validated
            $config = AiConfiguration::global()->forPurpose($purpose)->first();
            if ($config) {
                $config->update(['validated_at' => now()]);
            }

            $this->message = ucfirst($purpose).' — conexão testada com sucesso!';
            $this->messageType = 'success';
            $this->loadConfigs();
        } catch (\Throwable $e) {
            // Restore original key on failure
            if (isset($originalKey, $provider)) {
                config(["ai.providers.{$provider}.key" => $originalKey]);
            }

            $this->message = 'Falha na conexão: '.$e->getMessage();
            $this->messageType = 'error';
        }

        $this->testingPurpose = '';
    }

    public function toggleActive(string $purpose): void
    {
        $config = AiConfiguration::global()->forPurpose($purpose)->first();
        if ($config) {
            $config->update(['is_active' => ! $config->is_active]);
            $this->loadConfigs();
        }
    }

    private function loadConfigs(): void
    {
        foreach (['llm', 'embeddings'] as $purpose) {
            $config = AiConfiguration::global()->forPurpose($purpose)->first();

            if ($config) {
                $this->configs[$purpose] = [
                    'provider' => $config->provider,
                    'api_key' => $config->api_key,
                    'model' => $config->settings['model'] ?? '',
                    'base_url' => $config->settings['base_url'] ?? '',
                    'is_active' => $config->is_active,
                    'validated_at' => $config->validated_at?->format('d/m/Y H:i'),
                    'source' => 'database',
                ];
            } else {
                // Show env fallback info
                $defaultProvider = $purpose === 'embeddings'
                    ? config('ai.default_for_embeddings', 'voyageai')
                    : config('ai.default', 'anthropic');

                $envKey = config("ai.providers.{$defaultProvider}.key", '');

                $this->configs[$purpose] = [
                    'provider' => $defaultProvider,
                    'api_key' => '',
                    'model' => '',
                    'base_url' => '',
                    'is_active' => true,
                    'validated_at' => null,
                    'source' => $envKey ? 'env' : 'none',
                ];
            }
        }
    }

    public function render()
    {
        return view('livewire.admin.ai-config-panel', [
            'providers' => AiConfiguration::$availableProviders,
            'purposes' => AiConfiguration::$purposes,
        ])->layout('layouts.app');
    }
}
