<?php

namespace App\Presentation\Livewire\Admin;

use App\Domain\AI\Contracts\RecommendationServiceInterface;
use App\Domain\Basket\Repositories\CestaRepositoryInterface;
use Livewire\Component;

class AiCestaRecommendation extends Component
{
    public ?array $suggestion = null;

    public bool $loading = false;

    public string $error = '';

    public int $limit = 5;

    public function fetchRecommendation(): void
    {
        $this->loading = true;
        $this->error = '';
        $this->suggestion = null;

        try {
            $cestaRepo = app(CestaRepositoryInterface::class);
            $cesta = $cestaRepo->findAtiva();

            if ($cesta === null) {
                $this->error = 'Não existe cesta ativa no sistema.';
                $this->loading = false;

                return;
            }

            $service = app(RecommendationServiceInterface::class);
            $result = $service->recommendForCesta($cesta->id(), $this->limit);

            $this->suggestion = [
                'suggestedTickers' => $result->suggestedTickers,
                'currentBasket' => $result->currentBasketSummary,
                'confidence' => $result->confidence,
                'generatedAt' => $result->generatedAt->format('d/m/Y H:i'),
            ];
        } catch (\Throwable $e) {
            $this->error = 'Erro ao gerar recomendação: '.$e->getMessage();
        }

        $this->loading = false;
    }

    public function applySuggestion(): void
    {
        if ($this->suggestion === null) {
            return;
        }

        $ativos = array_map(fn ($s) => [
            'ticker' => $s['ticker'],
            'percentual' => $s['percentual'],
        ], $this->suggestion['suggestedTickers']);

        $this->dispatch('cesta-suggestion-applied', ativos: $ativos)->to(CestaManager::class);
    }

    public function render()
    {
        return view('livewire.admin.ai-cesta-recommendation');
    }
}
