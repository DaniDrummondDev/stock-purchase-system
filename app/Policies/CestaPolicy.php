<?php

namespace App\Policies;

use App\Models\User;

class CestaPolicy
{
    public function view(User $user): bool
    {
        return $user->hasPermissionTo('basket.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('basket.create');
    }

    public function update(User $user): bool
    {
        return $user->hasPermissionTo('basket.update');
    }

    public function history(User $user): bool
    {
        return $user->hasPermissionTo('basket.history');
    }
}
