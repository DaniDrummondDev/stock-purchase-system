<div class="relative" x-data="{ open: false }">
    {{-- Bell Icon --}}
    <button @click="open = !open" class="relative p-1 text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 rounded-full">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
        </svg>

        @if($unreadCount > 0)
            <span class="absolute -top-1 -right-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-600 rounded-full">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </button>

    {{-- Dropdown --}}
    <div
        x-show="open"
        @click.outside="open = false"
        x-transition
        class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 z-50"
    >
        <div class="p-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-900">Notificações</h3>
                @if($unreadCount > 0)
                    <button wire:click="markAllRead" class="text-xs text-indigo-600 hover:text-indigo-800">
                        Marcar todas como lidas
                    </button>
                @endif
            </div>

            @if(count($recentNotifications) > 0)
                <div class="space-y-3">
                    @foreach($recentNotifications as $notification)
                        <div class="flex items-start space-x-3 p-2 rounded-md hover:bg-gray-50 cursor-pointer" wire:click="markAsRead('{{ $notification['id'] }}')">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center space-x-2">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ $notification['title'] }}</p>
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $notification['priority'] === 'critical' ? 'bg-red-100 text-red-800' : '' }}
                                        {{ $notification['priority'] === 'normal' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                        {{ $notification['priority'] === 'low' ? 'bg-gray-100 text-gray-600' : '' }}
                                    ">
                                        {{ $notification['priority'] === 'critical' ? 'Crítico' : ($notification['priority'] === 'normal' ? 'Normal' : 'Baixo') }}
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500 mt-0.5">{{ $notification['created_at'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-3 pt-3 border-t border-gray-100">
                    <a href="/notifications" class="block text-center text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                        Ver todas
                    </a>
                </div>
            @else
                <p class="text-sm text-gray-500 text-center py-4">Nenhuma notificação nova.</p>
            @endif
        </div>
    </div>
</div>
