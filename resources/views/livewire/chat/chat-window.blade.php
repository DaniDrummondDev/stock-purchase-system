<div class="flex flex-col h-[calc(100vh-8rem)]">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold text-gray-900">Assistente Financeiro</h1>
        <button wire:click="newSession" class="text-sm text-indigo-600 hover:text-indigo-800">
            Nova Conversa
        </button>
    </div>

    {{-- Messages area --}}
    <div class="flex-1 overflow-y-auto bg-gray-50 rounded-lg p-4 space-y-4" id="chat-messages">
        @if(empty($messages))
            <div class="text-center text-gray-400 mt-20">
                <svg class="mx-auto h-12 w-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                </svg>
                <p class="text-lg font-medium">Ola! Como posso ajudar?</p>
                <p class="text-sm mt-2">Pergunte sobre sua carteira, simule cenarios ou tire duvidas sobre investimentos.</p>
            </div>
        @endif

        @foreach($messages as $message)
            <div class="flex {{ $message['role'] === 'user' ? 'justify-end' : 'justify-start' }}">
                <div class="max-w-[75%] rounded-lg px-4 py-3 {{ $message['role'] === 'user' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-800 shadow-sm border' }}">
                    <p class="text-sm whitespace-pre-wrap">{{ $message['content'] }}</p>
                    <p class="text-xs mt-1 {{ $message['role'] === 'user' ? 'text-indigo-200' : 'text-gray-400' }}">
                        {{ $message['created_at'] }}
                    </p>
                </div>
            </div>
        @endforeach

        @if($loading)
            <div class="flex justify-start">
                <div class="bg-white rounded-lg px-4 py-3 shadow-sm border">
                    <div class="flex space-x-1">
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Error --}}
    @if($error)
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-2 rounded mt-2 text-sm">
            {{ $error }}
        </div>
    @endif

    {{-- Input area --}}
    <div class="mt-4">
        <form wire:submit="sendMessage" class="flex space-x-2">
            <input
                type="text"
                wire:model="currentMessage"
                placeholder="Pergunte sobre sua carteira, simule cenarios..."
                class="flex-1 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                @if($loading) disabled @endif
                autofocus
            >
            <button
                type="submit"
                class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 text-sm font-medium"
                @if($loading) disabled @endif
            >
                <span wire:loading.remove wire:target="sendMessage">Enviar</span>
                <span wire:loading wire:target="sendMessage">...</span>
            </button>
        </form>
    </div>
</div>

@script
<script>
    $wire.on('message-sent', () => {
        const el = document.getElementById('chat-messages');
        if (el) el.scrollTop = el.scrollHeight;
    });

    // Auto-scroll on new messages
    const observer = new MutationObserver(() => {
        const el = document.getElementById('chat-messages');
        if (el) el.scrollTop = el.scrollHeight;
    });

    const chatEl = document.getElementById('chat-messages');
    if (chatEl) observer.observe(chatEl, { childList: true, subtree: true });
</script>
@endscript
