<div class="bg-white rounded-lg shadow p-6 mt-6">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-800">Sugestão IA para Cesta</h3>
        <button wire:click="fetchRecommendation" wire:loading.attr="disabled"
            class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 disabled:opacity-50">
            <span wire:loading.remove wire:target="fetchRecommendation">Gerar Sugestão</span>
            <span wire:loading wire:target="fetchRecommendation">Processando...</span>
        </button>
    </div>

    @if($error)
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
            {{ $error }}
        </div>
    @endif

    @if($suggestion)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Current Basket --}}
            <div>
                <h4 class="text-sm font-medium text-gray-500 mb-2">Cesta Atual</h4>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Ticker</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">%</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($suggestion['currentBasket'] as $item)
                            <tr>
                                <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ $item['ticker'] }}</td>
                                <td class="px-4 py-2 text-sm text-right text-gray-600">{{ number_format($item['percentual'], 1) }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- AI Suggestion --}}
            <div>
                <h4 class="text-sm font-medium text-gray-500 mb-2">Sugestão IA</h4>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Ticker</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">%</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Score</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($suggestion['suggestedTickers'] as $item)
                            <tr>
                                <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ $item['ticker'] }}</td>
                                <td class="px-4 py-2 text-sm text-right text-gray-600">{{ number_format($item['percentual'], 1) }}%</td>
                                <td class="px-4 py-2 text-sm text-right text-gray-600">{{ number_format($item['similarityScore'] * 100, 0) }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Confidence bar --}}
        <div class="mt-4">
            <div class="flex items-center justify-between mb-1">
                <span class="text-sm text-gray-500">Confiança</span>
                <span class="text-sm font-medium text-gray-700">{{ number_format($suggestion['confidence'] * 100, 0) }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                @php $color = $suggestion['confidence'] >= 0.7 ? 'bg-green-500' : ($suggestion['confidence'] >= 0.4 ? 'bg-yellow-500' : 'bg-red-500'); @endphp
                <div class="{{ $color }} h-2 rounded-full" style="width: {{ $suggestion['confidence'] * 100 }}%"></div>
            </div>
            <p class="text-xs text-gray-400 mt-1">Gerado em {{ $suggestion['generatedAt'] }}</p>
        </div>

        {{-- Apply button --}}
        <div class="mt-4 flex justify-end">
            <button wire:click="applySuggestion"
                class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                Aplicar Sugestão
            </button>
        </div>

        {{-- Rationales --}}
        @if(count($suggestion['suggestedTickers']) > 0 && !empty($suggestion['suggestedTickers'][0]['rationale']))
            <div class="mt-4 border-t pt-4">
                <h4 class="text-sm font-medium text-gray-500 mb-2">Justificativas</h4>
                @foreach($suggestion['suggestedTickers'] as $item)
                    @if(!empty($item['rationale']))
                        <p class="text-sm text-gray-600 mb-1"><strong>{{ $item['ticker'] }}:</strong> {{ $item['rationale'] }}</p>
                    @endif
                @endforeach
            </div>
        @endif
    @endif
</div>
