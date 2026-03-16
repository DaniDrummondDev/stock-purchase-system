<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        <h2 class="text-lg font-semibold text-gray-900 mb-2">Verificação 2FA</h2>
        <p>Insira o código do seu aplicativo autenticador.</p>
    </div>

    <form method="POST" action="{{ route('2fa.verify') }}">
        @csrf
        <div>
            <x-input-label for="code" value="Código de verificação" />
            <x-text-input id="code" class="block mt-1 w-full" type="text" name="code" required autofocus
                placeholder="000000" maxlength="6" pattern="[0-9]{6}" />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <x-primary-button class="mt-4 w-full justify-center">
            Verificar
        </x-primary-button>
    </form>
</x-guest-layout>
