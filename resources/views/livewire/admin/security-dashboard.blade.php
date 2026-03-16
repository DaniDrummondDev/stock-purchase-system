<div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Security Dashboard</h1>

    {{-- Event Counts --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">Critical (24h)</p>
            <p class="text-2xl font-bold {{ $eventCounts['critical_24h'] > 0 ? 'text-red-600' : 'text-green-600' }}">{{ $eventCounts['critical_24h'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">High (24h)</p>
            <p class="text-2xl font-bold {{ $eventCounts['high_24h'] > 0 ? 'text-orange-600' : 'text-green-600' }}">{{ $eventCounts['high_24h'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">Medium (7d)</p>
            <p class="text-2xl font-bold text-gray-700">{{ $eventCounts['medium_7d'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">Total (30d)</p>
            <p class="text-2xl font-bold text-gray-700">{{ $eventCounts['total_30d'] }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Blocked IPs --}}
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <h2 class="text-lg font-semibold p-4 pb-2">IPs Bloqueados</h2>
            @if(empty($blockedIps))
                <p class="p-4 pt-0 text-sm text-gray-500">Nenhum IP bloqueado.</p>
            @else
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">IP</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Razão</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Até</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($blockedIps as $ip)
                            <tr>
                                <td class="px-4 py-2 text-sm font-mono">{{ $ip['ip'] }}</td>
                                <td class="px-4 py-2 text-sm">{{ $ip['reason'] }}</td>
                                <td class="px-4 py-2 text-sm">{{ $ip['until'] }}</td>
                                <td class="px-4 py-2">
                                    <button wire:click="unblockIp('{{ $ip['id'] }}')" class="text-xs text-red-600 hover:text-red-800">Desbloquear</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        {{-- Locked Users --}}
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <h2 class="text-lg font-semibold p-4 pb-2">Contas Bloqueadas</h2>
            @if(empty($lockedUsers))
                <p class="p-4 pt-0 text-sm text-gray-500">Nenhuma conta bloqueada.</p>
            @else
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tentativas</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Até</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($lockedUsers as $user)
                            <tr>
                                <td class="px-4 py-2 text-sm">{{ $user['email'] }}</td>
                                <td class="px-4 py-2 text-sm text-center">{{ $user['attempts'] }}</td>
                                <td class="px-4 py-2 text-sm">{{ $user['locked_until'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    {{-- Recent Events --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <h2 class="text-lg font-semibold p-4 pb-2">Eventos Recentes</h2>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Severidade</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">IP</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Resource</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($recentEvents as $event)
                    <tr>
                        <td class="px-4 py-2 text-sm text-gray-500">{{ $event['date'] }}</td>
                        <td class="px-4 py-2 text-sm font-medium">{{ $event['type'] }}</td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-1 text-xs rounded-full
                                {{ $event['severity'] === 'critical' ? 'bg-red-100 text-red-800' : '' }}
                                {{ $event['severity'] === 'high' ? 'bg-orange-100 text-orange-800' : '' }}
                                {{ $event['severity'] === 'medium' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $event['severity'] === 'low' ? 'bg-gray-100 text-gray-600' : '' }}">
                                {{ $event['severity'] }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-sm font-mono text-gray-500">{{ $event['ip'] }}</td>
                        <td class="px-4 py-2 text-sm text-gray-500">{{ $event['resource'] ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Nenhum evento registado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
