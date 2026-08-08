<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    // gestión de usuarios y roles: solo ADMINISTRADOR (ver SEG-001)
    public function viewAny(User $user): bool
    {
        return $user->hasRole(config('app.ROLE_ADMIN'));
    }

    public function create(User $user): bool
    {
        return $user->hasRole(config('app.ROLE_ADMIN'));
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasRole(config('app.ROLE_ADMIN'));
    }

    public function delete(User $user, User $model): bool
    {
        return $user->hasRole(config('app.ROLE_ADMIN'));
    }
}
