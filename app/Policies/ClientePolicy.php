<?php

namespace App\Policies;

use App\Models\User;

class ClientePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('client.view.any');
    }

    public function view(User $user, string $clienteId): bool
    {
        return $user->hasPermissionTo('client.view.any')
            || ($user->hasPermissionTo('client.view') && $user->cliente_id === $clienteId);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('client.create');
    }

    public function update(User $user, string $clienteId): bool
    {
        return $user->hasPermissionTo('client.update.any')
            || ($user->hasPermissionTo('client.update') && $user->cliente_id === $clienteId);
    }

    public function delete(User $user): bool
    {
        return $user->hasPermissionTo('client.delete');
    }
}
