<?php

namespace App\Policies;

use App\Models\Arquivo;
use App\Models\User;
use App\Policies\Concerns\ChecksRoleAccess;

class ArquivoPolicy
{
    use ChecksRoleAccess;

    public function viewAny(User $user): bool
    {
        return $this->internal($user);
    }

    public function view(User $user, Arquivo $arquivo): bool
    {
        return $this->internal($user);
    }

    public function create(User $user): bool
    {
        return $this->internal($user);
    }

    public function uploadOriginal(User $user): bool
    {
        return $this->internal($user);
    }

    public function update(User $user, Arquivo $arquivo): bool
    {
        return $this->internal($user);
    }

    public function replaceOriginal(User $user, Arquivo $arquivo): bool
    {
        return $this->internal($user);
    }

    public function delete(User $user, Arquivo $arquivo): bool
    {
        return $this->internal($user);
    }
}
