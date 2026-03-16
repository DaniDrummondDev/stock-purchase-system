<?php

use App\Http\Controllers\ProfileController;
use App\Presentation\Livewire\Admin\CestaManager;
use App\Presentation\Livewire\Admin\ComprasPanel;
use App\Presentation\Livewire\Admin\ContaMasterPanel;
use App\Presentation\Livewire\Admin\SecurityDashboard;
use App\Presentation\Livewire\Chat\ChatWindow;
use App\Presentation\Livewire\Dashboard\ClienteDashboard;
use App\Presentation\Livewire\Notifications\AlertPreferences;
use App\Presentation\Livewire\Notifications\NotificationFeed;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Authenticated pages (all users)
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', ClienteDashboard::class)->name('dashboard');
    Route::get('/chat', ChatWindow::class)->name('chat');
    Route::get('/notifications', NotificationFeed::class)->name('notifications');
    Route::get('/notifications/preferences', AlertPreferences::class)->name('notifications.preferences');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin pages (admin, analyst, auditor only)
Route::middleware(['auth', 'role:admin|analyst|auditor'])->group(function () {
    Route::get('/admin/cesta', CestaManager::class);
    Route::get('/admin/compras', ComprasPanel::class);
    Route::get('/admin/master', ContaMasterPanel::class);
});

// Security dashboard (admin, auditor only)
Route::middleware(['auth', 'role:admin|auditor'])->group(function () {
    Route::get('/admin/security', SecurityDashboard::class);
});

require __DIR__.'/auth.php';
