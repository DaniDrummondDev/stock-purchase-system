<div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Preferências de Alertas</h1>

    @if($message)
        <div class="mb-4 p-4 rounded-md {{ $messageType === 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800' }}">
            {{ $message }}
        </div>
    @endif

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo de Alerta</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ativo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Canais</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($preferences as $pref)
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">
                            {{ $pref['label'] }}
                        </td>
                        <td class="px-6 py-4">
                            <button
                                wire:click="toggleTrigger('{{ $pref['id'] }}')"
                                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 {{ $pref['enabled'] ? 'bg-indigo-600' : 'bg-gray-200' }}"
                                role="switch"
                                aria-checked="{{ $pref['enabled'] ? 'true' : 'false' }}"
                            >
                                <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $pref['enabled'] ? 'translate-x-5' : 'translate-x-0' }}"></span>
                            </button>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex space-x-4">
                                <label class="flex items-center space-x-2 text-sm text-gray-500">
                                    <input type="checkbox" checked disabled class="rounded border-gray-300 text-indigo-600">
                                    <span>In-App</span>
                                </label>
                                <label class="flex items-center space-x-2 text-sm text-gray-700">
                                    <input
                                        type="checkbox"
                                        {{ in_array('email', $pref['channels']) ? 'checked' : '' }}
                                        wire:click="updateChannels('{{ $pref['id'] }}', {{ json_encode(
                                            in_array('email', $pref['channels'])
                                                ? array_values(array_diff($pref['channels'], ['email']))
                                                : array_merge($pref['channels'], ['email'])
                                        ) }})"
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    >
                                    <span>Email</span>
                                </label>
                                <label class="flex items-center space-x-2 text-sm text-gray-700">
                                    <input
                                        type="checkbox"
                                        {{ in_array('proactive_chat', $pref['channels']) ? 'checked' : '' }}
                                        wire:click="updateChannels('{{ $pref['id'] }}', {{ json_encode(
                                            in_array('proactive_chat', $pref['channels'])
                                                ? array_values(array_diff($pref['channels'], ['proactive_chat']))
                                                : array_merge($pref['channels'], ['proactive_chat'])
                                        ) }})"
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    >
                                    <span>Chat Proativo</span>
                                </label>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <button
                                wire:click="toggleTrigger('{{ $pref['id'] }}')"
                                class="text-sm text-indigo-600 hover:text-indigo-900"
                            >
                                {{ $pref['enabled'] ? 'Desativar' : 'Ativar' }}
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
