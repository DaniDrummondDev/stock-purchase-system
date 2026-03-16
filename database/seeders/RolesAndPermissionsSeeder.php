<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Client BC
            'client.create', 'client.view', 'client.view.any',
            'client.update', 'client.update.any', 'client.delete',
            // Basket BC
            'basket.create', 'basket.update', 'basket.view', 'basket.history',
            // PurchaseEngine BC
            'purchase.execute', 'purchase.view', 'purchase.view.any',
            // Tax BC
            'tax.view', 'tax.view.any', 'tax.kafka.publish',
            // Rebalancing BC
            'rebalancing.execute', 'rebalancing.view',
            // MarketData BC
            'market.import', 'market.view',
            // AI BC
            'ai.chat', 'ai.recommendation', 'ai.risk.view', 'ai.config.manage',
            // Security BC
            'security.events.view', 'security.users.manage',
            'security.roles.manage', 'security.ip.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create roles and assign permissions
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions($permissions);

        $analyst = Role::firstOrCreate(['name' => 'analyst', 'guard_name' => 'web']);
        $analyst->syncPermissions([
            'client.create', 'client.view', 'client.view.any',
            'basket.create', 'basket.update', 'basket.view', 'basket.history',
            'purchase.execute', 'purchase.view', 'purchase.view.any',
            'tax.view', 'tax.view.any', 'tax.kafka.publish',
            'rebalancing.execute', 'rebalancing.view',
            'market.import', 'market.view',
            'ai.chat', 'ai.recommendation', 'ai.risk.view',
        ]);

        $auditor = Role::firstOrCreate(['name' => 'auditor', 'guard_name' => 'web']);
        $auditor->syncPermissions([
            'client.view', 'client.view.any',
            'basket.view', 'basket.history',
            'purchase.view', 'purchase.view.any',
            'tax.view', 'tax.view.any',
            'rebalancing.view',
            'market.view',
            'ai.risk.view',
            'security.events.view',
        ]);

        $client = Role::firstOrCreate(['name' => 'client', 'guard_name' => 'web']);
        $client->syncPermissions([
            'client.view', 'client.update',
            'basket.view',
            'purchase.view',
            'tax.view',
            'market.view',
            'ai.chat', 'ai.risk.view',
        ]);
    }
}
