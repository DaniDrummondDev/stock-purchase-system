<?php

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analyst', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'auditor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'client', 'guard_name' => 'web']);
});

// ── Web Routes: Admin pages ──────────────────────────────────────

test('admin can access /admin/cesta', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get('/admin/cesta')
        ->assertSuccessful();
});

test('client gets 403 on /admin/cesta', function () {
    $client = User::factory()->create(['role' => 'client']);
    $client->assignRole('client');

    $this->actingAs($client)
        ->get('/admin/cesta')
        ->assertForbidden();
});

test('client gets 403 on /admin/compras', function () {
    $client = User::factory()->create(['role' => 'client']);
    $client->assignRole('client');

    $this->actingAs($client)
        ->get('/admin/compras')
        ->assertForbidden();
});

test('client gets 403 on /admin/master', function () {
    $client = User::factory()->create(['role' => 'client']);
    $client->assignRole('client');

    $this->actingAs($client)
        ->get('/admin/master')
        ->assertForbidden();
});

test('client gets 403 on /admin/security', function () {
    $client = User::factory()->create(['role' => 'client']);
    $client->assignRole('client');

    $this->actingAs($client)
        ->get('/admin/security')
        ->assertForbidden();
});

test('unauthenticated user is redirected from admin routes', function () {
    $this->get('/admin/cesta')->assertRedirect('/login');
    $this->get('/admin/compras')->assertRedirect('/login');
    $this->get('/admin/master')->assertRedirect('/login');
    $this->get('/admin/security')->assertRedirect('/login');
});

// ── API Routes: Admin endpoints ──────────────────────────────────

test('client cannot access admin API endpoints', function () {
    $client = User::factory()->create(['role' => 'client']);
    $client->assignRole('client');

    $this->actingAs($client, 'sanctum')
        ->getJson('/api/admin/cesta/atual')
        ->assertForbidden();

    $this->actingAs($client, 'sanctum')
        ->getJson('/api/admin/motor/compras')
        ->assertForbidden();

    $this->actingAs($client, 'sanctum')
        ->postJson('/api/admin/rebalanceamento/executar')
        ->assertForbidden();
});

test('admin can access admin API endpoints', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $admin->assignRole('admin');

    // These may return 404/422 because there's no data, but NOT 403
    $response = $this->actingAs($admin, 'sanctum')
        ->getJson('/api/admin/cesta/atual');

    expect($response->status())->not->toBe(403);
});

// ── Notifications table ──────────────────────────────────────────

test('notifications table exists and accepts inserts', function () {
    $user = User::factory()->create();

    $user->notify(new class extends \Illuminate\Notifications\Notification
    {
        public function via($notifiable): array
        {
            return ['database'];
        }

        public function toArray($notifiable): array
        {
            return ['title' => 'Test', 'summary' => 'Works'];
        }
    });

    expect($user->notifications)->toHaveCount(1);
    expect($user->notifications->first()->data['title'])->toBe('Test');
});

test('notifications page loads for authenticated user', function () {
    $user = User::factory()->create(['role' => 'client']);
    $user->assignRole('client');

    $this->actingAs($user)
        ->get('/notifications')
        ->assertSuccessful();
});
