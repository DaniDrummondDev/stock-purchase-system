<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        <h2 class="text-lg font-semibold text-gray-900 mb-2">Configurar Autenticação 2FA</h2>
        <p>Escaneie o QR code abaixo com o Google Authenticator ou Authy.</p>
    </div>

    <div class="mb-4 text-center">
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($qrCodeUrl) }}" alt="QR Code 2FA" class="mx-auto">
    </div>

    <div class="mb-4 p-3 bg-gray-50 rounded text-center">
        <p class="text-xs text-gray-500 mb-1">Ou insira manualmente:</p>
        <code class="text-sm font-mono text-gray-800">{{ $secret }}</code>
    </div>

    <form method="POST" action="{{ route('2fa.confirm') }}">
        @csrf
        <div>
            <x-input-label for="code" value="Código de verificação" />
            <x-text-input id="code" class="block mt-1 w-full" type="text" name="code" required autofocus
                placeholder="000000" maxlength="6" pattern="[0-9]{6}" />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <x-primary-button class="mt-4 w-full justify-center">
            Ativar 2FA
        </x-primary-button>
    </form>
</x-guest-layout>
