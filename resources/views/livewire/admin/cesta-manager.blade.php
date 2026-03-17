<div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Gestão de Cesta Top Five</h1>

    @if($message)
        <div class="mb-4 p-4 rounded-md {{ $messageType === 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800' }}">
            {{ $message }}
        </div>
    @endif

    {{-- Form --}}
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4">{{ $cestaAtual ? 'Alterar Cesta' : 'Criar Nova Cesta' }}</h2>
        <form wire:submit="save">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Nome da Cesta</label>
                <input type="text" wire:model="nome" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Top Five - Março 2026">
            </div>

            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Ativos (5 obrigatórios, soma = 100%)</label>
                @foreach($ativos as $index => $ativo)
                    <div class="flex space-x-2" wire:key="ativo-{{ $index }}-{{ $ativo['ticker'] ?? '' }}-{{ $ativo['percentual'] ?? '' }}">
                        <input type="text" wire:model="ativos.{{ $index }}.ticker" class="w-32 rounded-md border-gray-300 shadow-sm sm:text-sm" placeholder="PETR4">
                        <input type="number" wire:model="ativos.{{ $index }}.percentual" step="0.01" class="w-24 rounded-md border-gray-300 shadow-sm sm:text-sm" placeholder="%">
                        <span class="text-gray-400 text-sm self-center">%</span>
                    </div>
                @endforeach
            </div>

            <button type="submit" class="mt-4 bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 text-sm font-medium">
                {{ $cestaAtual ? 'Atualizar Cesta' : 'Criar Cesta' }}
            </button>
        </form>
    </div>

    {{-- Current Basket --}}
    @if($cestaAtual)
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Cesta Ativa: {{ $cestaAtual['nome'] }}</h2>
            <div class="grid grid-cols-5 gap-4">
                @foreach($cestaAtual['ativos'] as $ativo)
                    <div class="bg-indigo-50 rounded-lg p-3 text-center">
                        <p class="font-bold text-indigo-700">{{ $ativo['ticker'] }}</p>
                        <p class="text-sm text-gray-600">{{ $ativo['percentual'] }}%</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- AI Recommendation --}}
    <livewire:admin.ai-cesta-recommendation />

    {{-- History --}}
    @if(!empty($historico))
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <h2 class="text-lg font-semibold p-6 pb-3">Histórico de Cestas</h2>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nome</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ativos</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($historico as $cesta)
                        <tr>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $cesta['nome'] }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ implode(', ', $cesta['ativos']) }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded-full {{ $cesta['ativo'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $cesta['ativo'] ? 'Ativa' : 'Desativada' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $cesta['data'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
