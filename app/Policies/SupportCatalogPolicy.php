<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksRoleAccess;

class SupportCatalogPolicy
{
    use ChecksRoleAccess;

    public function viewAny(User $user): bool
    {
        return $this->internal($user);
    }

    public function view(User $user, mixed $model = null): bool
    {
        return $this->internal($user);
    }

    public function create(User $user): bool
    {
        return $this->internal($user);
    }

    public function update(User $user, mixed $model = null): bool
    {
        return $this->internal($user);
    }

    public function delete(User $user, mixed $model = null): bool
    {
        return $this->internal($user);
    }
}
