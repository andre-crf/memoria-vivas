<?php

namespace App\Policies\Concerns;

use App\Models\User;

trait ChecksRoleAccess
{
    protected function internal(User $user): bool
    {
        return $user->canAccessAdminArea();
    }

    protected function admin(User $user): bool
    {
        return $user->ativo() && $user->isAdmin();
    }
}
