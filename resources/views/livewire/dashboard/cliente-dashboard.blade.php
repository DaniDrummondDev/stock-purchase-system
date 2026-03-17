<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">
            {{ $isAdmin ? 'Dashboard — Gestão de Carteiras' : 'Minha Carteira' }}
        </h1>

        @if($isAdmin && count($clientes) > 1)
            <select wire:model.live="clienteId" class="mt-2 block w-full max-w-xs rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                @foreach($clientes as $cliente)
                    <option value="{{ $cliente['id'] }}">{{ $cliente['nome'] }}</option>
                @endforeach
            </select>
        @elseif(!empty($clientes))
            <p class="mt-1 text-sm text-gray-500">{{ $clientes[0]['nome'] ?? '' }}</p>
        @endif
    </div>

    @if($message)
        <div class="mb-4 p-4 rounded-lg {{ $messageType === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200' }}">
            {{ $message }}
        </div>
    @endif

    {{-- Valor Mensal Card --}}
    @if($valorMensal > 0)
        <div class="bg-white rounded-lg shadow p-4 mb-6 flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Valor Mensal de Investimento</p>
                @if($editandoValor)
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-sm text-gray-500">R$</span>
                        <input type="number" wire:model="novoValorMensal" min="100" step="50" class="w-32 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        <button wire:click="alterarValorMensal" class="px-3 py-1 bg-indigo-600 text-white text-xs rounded-md hover:bg-indigo-700">Salvar</button>
                        <button wire:click="$set('editandoValor', false)" class="px-3 py-1 bg-gray-200 text-gray-700 text-xs rounded-md hover:bg-gray-300">Cancelar</button>
                    </div>
                @else
                    <p class="text-xl font-bold text-indigo-600">R$ {{ number_format($valorMensal, 2, ',', '.') }}</p>
                    <p class="text-xs text-gray-400">Aporte por data: R$ {{ number_format($valorMensal / 3, 2, ',', '.') }} (dias 5, 15 e 25)</p>
                @endif
            </div>
            @if(!$editandoValor && !$isAdmin)
                <button wire:click="$set('editandoValor', true)" class="px-3 py-2 text-sm text-indigo-600 border border-indigo-300 rounded-md hover:bg-indigo-50">Alterar</button>
            @endif
        </div>
    @endif

    {{-- Cesta Ativa Card --}}
    @if($cestaAtiva)
        <div class="bg-white rounded-lg shadow p-4 mb-6">
            <h3 class="text-sm font-medium text-gray-500 mb-3">Cesta Ativa: {{ $cestaAtiva['nome'] }}</h3>
            <div class="grid grid-cols-5 gap-3">
                @foreach($cestaAtiva['ativos'] as $ativo)
                    <div class="bg-indigo-50 rounded-lg p-3 text-center">
                        <p class="font-bold text-indigo-700">{{ $ativo['ticker'] }}</p>
                        <p class="text-sm text-gray-600">{{ $ativo['percentual'] }}%</p>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6 text-sm text-yellow-800">
            Nenhuma cesta ativa no momento.
        </div>
    @endif

    @if(!empty($carteira))
        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm text-gray-500">Valor Investido</p>
                <p class="text-xl font-bold">R$ {{ number_format($valorInvestido, 2, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm text-gray-500">Valor Atual</p>
                <p class="text-xl font-bold">R$ {{ number_format($saldoTotal, 2, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm text-gray-500">P/L Total</p>
                <p class="text-xl font-bold {{ $plTotal >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    {{ $plTotal >= 0 ? '+' : '' }}R$ {{ number_format($plTotal, 2, ',', '.') }}
                </p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm text-gray-500">Rentabilidade</p>
                <p class="text-xl font-bold {{ $rentabilidade >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    {{ $rentabilidade >= 0 ? '+' : '' }}{{ number_format($rentabilidade, 2, ',', '.') }}%
                </p>
            </div>
        </div>

        {{-- Portfolio Table (RN-063 to RN-070) --}}
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ativo</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Qtd</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">PM</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Cotação</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Valor</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">P/L</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">%</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($carteira as $ativo)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $ativo['ticker'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">{{ $ativo['quantidade'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">R$ {{ $ativo['precoMedio'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">R$ {{ $ativo['cotacaoAtual'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">R$ {{ $ativo['valorAtual'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm {{ $ativo['plClass'] }} text-right font-medium">R$ {{ $ativo['plFormatted'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">{{ $ativo['composicao'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="bg-white rounded-lg shadow p-8 text-center">
            @if($cestaAtiva)
                <p class="text-gray-700 font-medium mb-2">Sua carteira ainda não possui ativos.</p>
                <p class="text-sm text-gray-500">A primeira compra será executada automaticamente no próximo dia de aporte (5, 15 ou 25), distribuindo seu investimento entre os ativos da cesta <strong>{{ $cestaAtiva['nome'] }}</strong>.</p>
            @else
                <p class="text-gray-500">Nenhum ativo na carteira e nenhuma cesta ativa no momento.</p>
            @endif
        </div>
    @endif
</div>
