<?php

namespace App\Policies;

use App\Models\User;

class CompraPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('purchase.view.any');
    }

    public function view(User $user, ?string $clienteId = null): bool
    {
        return $user->hasPermissionTo('purchase.view.any')
            || ($user->hasPermissionTo('purchase.view') && $user->cliente_id === $clienteId);
    }

    public function execute(User $user): bool
    {
        return $user->hasPermissionTo('purchase.execute');
    }
}
