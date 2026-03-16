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
        <div class="bg-white rounded-lg shadow p-8 text-center text-gray-500">
            Nenhum ativo na carteira deste cliente.
        </div>
    @endif
</div>
