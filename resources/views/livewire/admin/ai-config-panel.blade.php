<div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Configuração de AI Providers</h1>

    {{-- Flash Message --}}
    @if($message)
        <div class="mb-6 p-4 rounded-lg {{ $messageType === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200' }}">
            {{ $message }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @foreach(['llm' => 'Text Generation (LLM)', 'embeddings' => 'Embeddings'] as $purpose => $label)
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">{{ $label }}</h2>
                    <div class="flex items-center gap-2">
                        {{-- Source badge --}}
                        @if(($configs[$purpose]['source'] ?? 'none') === 'database')
                            <span class="px-2 py-1 text-xs rounded-full bg-indigo-100 text-indigo-800">DB Global</span>
                        @elseif(($configs[$purpose]['source'] ?? 'none') === 'env')
                            <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">.env fallback</span>
                        @else
                            <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-600">Não configurado</span>
                        @endif

                        {{-- Active toggle --}}
                        @if(($configs[$purpose]['source'] ?? 'none') === 'database')
                            <button wire:click="toggleActive('{{ $purpose }}')" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ ($configs[$purpose]['is_active'] ?? false) ? 'bg-indigo-600' : 'bg-gray-300' }}">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ ($configs[$purpose]['is_active'] ?? false) ? 'translate-x-6' : 'translate-x-1' }}"></span>
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Provider Select --}}
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Provider</label>
                    <select wire:model="configs.{{ $purpose }}.provider" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        @foreach($providers as $key => $name)
                            <option value="{{ $key }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- API Key --}}
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">API Key</label>
                    <input type="password" wire:model="configs.{{ $purpose }}.api_key" placeholder="sk-..." class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-mono">
                </div>

                {{-- Model (optional) --}}
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Model <span class="text-gray-400">(opcional)</span></label>
                    <input type="text" wire:model="configs.{{ $purpose }}.model" placeholder="ex: gpt-4o, claude-sonnet-4-20250514" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                </div>

                {{-- Base URL (for Ollama/self-hosted) --}}
                @if(($configs[$purpose]['provider'] ?? '') === 'ollama')
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Base URL</label>
                        <input type="text" wire:model="configs.{{ $purpose }}.base_url" placeholder="http://localhost:11434" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-mono">
                    </div>
                @endif

                {{-- Validated badge --}}
                @if($configs[$purpose]['validated_at'] ?? null)
                    <div class="mb-4">
                        <span class="inline-flex items-center gap-1 px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                            Validado em {{ $configs[$purpose]['validated_at'] }}
                        </span>
                    </div>
                @endif

                {{-- Action Buttons --}}
                <div class="flex gap-3">
                    <button wire:click="save('{{ $purpose }}')" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        Salvar
                    </button>
                    <button wire:click="testConnection('{{ $purpose }}')" class="px-4 py-2 bg-white text-gray-700 text-sm font-medium rounded-md border border-gray-300 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500" {{ $testingPurpose === $purpose ? 'disabled' : '' }}>
                        @if($testingPurpose === $purpose)
                            <span class="inline-flex items-center gap-1">
                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                Testando...
                            </span>
                        @else
                            Testar Conexão
                        @endif
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Info box --}}
    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h3 class="text-sm font-medium text-blue-800 mb-2">Como funciona a resolução de provider</h3>
        <ol class="text-sm text-blue-700 list-decimal list-inside space-y-1">
            <li><strong>Configuração do banco (global)</strong> — definida nesta página, tem prioridade máxima</li>
            <li><strong>Variáveis de ambiente (.env)</strong> — fallback quando não há config no banco</li>
        </ol>
        <p class="text-sm text-blue-600 mt-2">O sistema usa o <code class="bg-blue-100 px-1 rounded">AiConfigResolver</code> para resolver automaticamente qual provider usar em cada operação.</p>
    </div>
</div>
