<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AiConfiguration extends Model
{
    use HasUuids;

    protected $table = 'ai_configurations';

    protected $fillable = [
        'scope',
        'user_id',
        'provider',
        'purpose',
        'api_key',
        'settings',
        'is_active',
        'validated_at',
    ];

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'settings' => 'json',
            'is_active' => 'boolean',
            'validated_at' => 'datetime',
        ];
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'user_id');
    }

    public function scopeGlobal($query)
    {
        return $query->where('scope', 'global');
    }

    public function scopeForUser($query, string $userId)
    {
        return $query->where('scope', 'user')->where('user_id', $userId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForPurpose($query, string $purpose)
    {
        return $query->where('purpose', $purpose);
    }

    public static array $availableProviders = [
        'anthropic' => 'Anthropic (Claude)',
        'openai' => 'OpenAI (GPT)',
        'gemini' => 'Google (Gemini)',
        'ollama' => 'Ollama (Local)',
        'mistral' => 'Mistral AI',
        'groq' => 'Groq',
        'deepseek' => 'DeepSeek',
        'voyageai' => 'Voyage AI',
        'openrouter' => 'OpenRouter',
        'xai' => 'xAI (Grok)',
    ];

    public static array $purposes = [
        'llm' => 'Text Generation (LLM)',
        'embeddings' => 'Embeddings',
    ];
}
