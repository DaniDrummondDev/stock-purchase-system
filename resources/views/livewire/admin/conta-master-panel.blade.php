<div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Conta Master (Resíduos)</h1>

    @if(empty($saldos))
        <div class="bg-white rounded-lg shadow p-8 text-center text-gray-500">
            Nenhum resíduo na conta master.
        </div>
    @else
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ticker</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Quantidade</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">PM</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($saldos as $saldo)
                        <tr>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $saldo['ticker'] }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500 text-right">{{ $saldo['quantidade'] }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500 text-right">R$ {{ $saldo['precoMedio'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
