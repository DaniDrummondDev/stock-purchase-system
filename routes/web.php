<?php

use App\Presentation\Livewire\Admin\CestaManager;
use App\Presentation\Livewire\Admin\ComprasPanel;
use App\Presentation\Livewire\Admin\ContaMasterPanel;
use App\Presentation\Livewire\Dashboard\ClienteDashboard;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Livewire pages
Route::get('/dashboard', ClienteDashboard::class);
Route::get('/admin/cesta', CestaManager::class);
Route::get('/admin/compras', ComprasPanel::class);
Route::get('/admin/master', ContaMasterPanel::class);
