<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    // Create roles and permissions for testing
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $clientRole = Role::firstOrCreate(['name' => 'client', 'guard_name' => 'web']);

    $permissions = ['client.view', 'client.view.any', 'basket.create', 'security.users.manage'];

    foreach ($permissions as $p) {
        Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
    }

    $adminRole->syncPermissions($permissions);
    $clientRole->syncPermissions(['client.view']);
});

test('admin has all permissions', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $admin->assignRole('admin');

    expect($admin->hasPermissionTo('client.view'))->toBeTrue();
    expect($admin->hasPermissionTo('client.view.any'))->toBeTrue();
    expect($admin->hasPermissionTo('basket.create'))->toBeTrue();
    expect($admin->hasPermissionTo('security.users.manage'))->toBeTrue();
});

test('client has limited permissions', function () {
    $client = User::factory()->create(['role' => 'client']);
    $client->assignRole('client');

    expect($client->hasPermissionTo('client.view'))->toBeTrue();
    expect($client->hasPermissionTo('client.view.any'))->toBeFalse();
    expect($client->hasPermissionTo('basket.create'))->toBeFalse();
    expect($client->hasPermissionTo('security.users.manage'))->toBeFalse();
});

test('user without role has no permissions', function () {
    $user = User::factory()->create();

    expect($user->getAllPermissions())->toHaveCount(0);
});
