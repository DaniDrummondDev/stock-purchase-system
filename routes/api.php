<?php

use App\Presentation\Http\Controllers\Api\CestaController;
use App\Presentation\Http\Controllers\Api\ClienteController;
use Illuminate\Support\Facades\Route;

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
