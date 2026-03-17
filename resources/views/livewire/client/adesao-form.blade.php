<div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Adesão ao Programa de Compra Programada</h1>

    @if($message)
        <div class="mb-6 p-4 rounded-lg {{ $messageType === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200' }}">
            {{ $message }}
        </div>
    @endif

    @if($jaAderiu)
        <div class="bg-white rounded-lg shadow p-8 text-center">
            <div class="mb-4">
                <svg class="mx-auto h-12 w-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <h2 class="text-lg font-semibold text-gray-900 mb-2">Você já aderiu ao programa!</h2>
            <p class="text-gray-600 mb-4">Acesse o Dashboard para acompanhar sua carteira.</p>
            <a href="/dashboard" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                Ir para o Dashboard
            </a>
        </div>
    @else
        <div class="bg-white rounded-lg shadow p-6 max-w-lg">
            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <h3 class="text-sm font-medium text-blue-800 mb-1">Como funciona</h3>
                <p class="text-sm text-blue-700">Ao aderir, você define um valor mensal de investimento. O sistema compra automaticamente ações da cesta Top Five nos dias 5, 15 e 25 de cada mês, distribuindo proporcionalmente entre os 5 ativos recomendados.</p>
            </div>

            <form wire:submit="aderir">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome completo</label>
                    <input type="text" wire:model="nome" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    @error('nome') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">CPF <span class="text-gray-400">(apenas números)</span></label>
                    <input type="text" wire:model="cpf" maxlength="11" placeholder="12345678909" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-mono">
                    @error('cpf') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" wire:model="email" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    @error('email') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Valor mensal (R$)</label>
                    <input type="number" wire:model="valorMensal" min="100" step="50" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    <p class="mt-1 text-xs text-gray-500">Mínimo R$ 100,00. Será dividido em 3 aportes por mês (dias 5, 15 e 25).</p>
                    @error('valorMensal') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                <button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    Aderir ao Programa
                </button>
            </form>
        </div>
    @endif
</div>
