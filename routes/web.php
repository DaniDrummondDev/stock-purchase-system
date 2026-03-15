<?php

use App\Http\Controllers\ProfileController;
use App\Presentation\Livewire\Admin\CestaManager;
use App\Presentation\Livewire\Admin\ComprasPanel;
use App\Presentation\Livewire\Admin\ContaMasterPanel;
use App\Presentation\Livewire\Dashboard\ClienteDashboard;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Authenticated Livewire pages
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', ClienteDashboard::class)->name('dashboard');
    Route::get('/admin/cesta', CestaManager::class);
    Route::get('/admin/compras', ComprasPanel::class);
    Route::get('/admin/master', ContaMasterPanel::class);

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
