<div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Compras Programadas</h1>

    @if(empty($compras))
        <div class="bg-white rounded-lg shadow p-8 text-center text-gray-500">
            Nenhuma compra executada ainda.
        </div>
    @else
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Valor Total</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Participantes</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Distribuições</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($compras as $compra)
                        <tr>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $compra['dataExecucao'] }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500 text-right">R$ {{ $compra['valorTotal'] }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500 text-center">{{ $compra['participantes'] }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500 text-center">{{ $compra['distribuicoes'] }}</td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2 py-1 text-xs rounded-full
                                    {{ $compra['status'] === 'concluida' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $compra['status'] === 'processando' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                    {{ $compra['status'] === 'erro' ? 'bg-red-100 text-red-800' : '' }}
                                    {{ $compra['status'] === 'pendente' ? 'bg-gray-100 text-gray-600' : '' }}">
                                    {{ ucfirst($compra['status']) }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
