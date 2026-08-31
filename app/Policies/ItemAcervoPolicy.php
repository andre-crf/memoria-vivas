<?php

namespace App\Policies;

use App\Models\ItemAcervo;
use App\Models\User;
use App\Policies\Concerns\ChecksRoleAccess;

class ItemAcervoPolicy
{
    use ChecksRoleAccess;

    public function viewAny(User $user): bool
    {
        return $this->internal($user);
    }

    public function view(User $user, ItemAcervo $itemAcervo): bool
    {
        return $this->internal($user);
    }

    public function create(User $user): bool
    {
        return $this->internal($user);
    }

    public function update(User $user, ItemAcervo $itemAcervo): bool
    {
        return $this->internal($user);
    }

    public function updateVisibility(User $user, ItemAcervo $itemAcervo): bool
    {
        return $this->internal($user);
    }

    public function delete(User $user, ItemAcervo $itemAcervo): bool
    {
        return $this->internal($user);
    }

    public function restore(User $user, ItemAcervo $itemAcervo): bool
    {
        return $this->admin($user);
    }

    public function forceDelete(User $user, ItemAcervo $itemAcervo): bool
    {
        return $this->admin($user);
    }
}
