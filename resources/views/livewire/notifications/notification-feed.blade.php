<div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Notificações</h1>

    {{-- Filter Tabs --}}
    <div class="flex space-x-1 mb-6 bg-gray-100 rounded-lg p-1 w-fit">
        @php
            $tabs = [
                'all' => 'Todas',
                'critical' => 'Críticas',
                'normal' => 'Normais',
                'low' => 'Baixas',
            ];
        @endphp
        @foreach($tabs as $key => $label)
            <button
                wire:click="setFilter('{{ $key }}')"
                class="px-4 py-2 text-sm font-medium rounded-md transition-colors duration-150
                    {{ $filter === $key ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Notification Cards --}}
    @if(count($notifications) > 0)
        <div class="space-y-3">
            @foreach($notifications as $notification)
                <div class="bg-white rounded-lg shadow p-4 flex items-start justify-between {{ !$notification['read'] ? 'border-l-4 border-indigo-500' : '' }}">
                    <div class="flex-1">
                        <div class="flex items-center space-x-2 mb-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $notification['priority'] === 'critical' ? 'bg-red-100 text-red-800' : '' }}
                                {{ $notification['priority'] === 'normal' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $notification['priority'] === 'low' ? 'bg-gray-100 text-gray-600' : '' }}
                            ">
                                {{ $notification['priority'] === 'critical' ? 'Crítico' : ($notification['priority'] === 'normal' ? 'Normal' : 'Baixo') }}
                            </span>
                            <span class="text-xs text-gray-500">{{ $notification['created_at'] }}</span>
                        </div>
                        <h3 class="text-sm font-medium text-gray-900">{{ $notification['title'] }}</h3>
                        @if($notification['summary'])
                            <p class="text-sm text-gray-600 mt-1">{{ $notification['summary'] }}</p>
                        @endif
                    </div>

                    @if(!$notification['read'])
                        <button
                            wire:click="markAsRead('{{ $notification['id'] }}')"
                            class="ml-4 text-xs text-indigo-600 hover:text-indigo-800 font-medium whitespace-nowrap"
                        >
                            Marcar como lida
                        </button>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <div class="bg-white rounded-lg shadow p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhuma notificação</h3>
            <p class="mt-1 text-sm text-gray-500">Você não tem notificações {{ $filter !== 'all' ? 'com este filtro' : '' }}.</p>
        </div>
    @endif
</div>
