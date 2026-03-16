<?php

use App\Presentation\Http\Controllers\Api\AiController;
use App\Presentation\Http\Controllers\Api\CestaController;
use App\Presentation\Http\Controllers\Api\ClienteController;
use App\Presentation\Http\Controllers\Api\CompraController;
use App\Presentation\Http\Controllers\Api\CotacaoController;
use App\Presentation\Http\Controllers\Api\RebalanceamentoController;
use Illuminate\Support\Facades\Route;

// Apply rate limiting to all API routes
Route::middleware('throttle:api')->group(function () {

    // Clientes
    Route::post('/clientes/adesao', [ClienteController::class, 'adesao']);
    Route::post('/clientes/{clienteId}/saida', [ClienteController::class, 'saida']);
    Route::put('/clientes/{clienteId}/valor-mensal', [ClienteController::class, 'alterarValorMensal']);
    Route::get('/clientes/{clienteId}/carteira', [ClienteController::class, 'carteira']);

    // Cesta Top Five (Admin)
    Route::prefix('admin/cesta')->group(function () {
        Route::post('/', [CestaController::class, 'store']);
        Route::get('/atual', [CestaController::class, 'atual']);
        Route::get('/historico', [CestaController::class, 'historico']);
    });

    // Cotações (Admin - importação)
    Route::post('/admin/cotacoes/importar', [CotacaoController::class, 'importar']);

    // Cotações (public)
    Route::get('/cotacoes/{ticker}/{data}', [CotacaoController::class, 'showByDate'])
        ->where('data', '\d{4}-\d{2}-\d{2}');
    Route::get('/cotacoes/{ticker}', [CotacaoController::class, 'show']);

    // Motor de Compra (Admin)
    Route::prefix('admin/motor')->group(function () {
        Route::post('/executar-compra', [CompraController::class, 'executar']);
        Route::get('/compras', [CompraController::class, 'index']);
        Route::get('/compras/{id}', [CompraController::class, 'show']);
    });

    // Rebalanceamento (Admin)
    Route::post('/admin/rebalanceamento/executar', [RebalanceamentoController::class, 'executar']);

    // AI
    Route::prefix('ai')->group(function () {
        Route::post('/recomendacao-cesta', [AiController::class, 'recomendacaoCesta']);
        Route::post('/chat', [AiController::class, 'chat']);
    });

}); // end throttle:api group
