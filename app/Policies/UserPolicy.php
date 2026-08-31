<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksRoleAccess;

class UserPolicy
{
    use ChecksRoleAccess;

    public function viewAny(User $user): bool
    {
        return $this->admin($user);
    }

    public function view(User $user, User $target): bool
    {
        return $this->admin($user);
    }

    public function create(User $user): bool
    {
        return $this->admin($user);
    }

    public function update(User $user, User $target): bool
    {
        return $this->admin($user);
    }

    public function updateIdentity(User $user, User $target): bool
    {
        return $this->admin($user);
    }

    public function updateRole(User $user, User $target, ?string $role = null): bool
    {
        if (! $this->admin($user)) {
            return false;
        }

        if ($role === null || $role === 'admin') {
            return true;
        }

        return ! $this->isLastActiveAdmin($target);
    }

    public function updateStatus(User $user, User $target, ?string $status = null): bool
    {
        if (! $this->admin($user)) {
            return false;
        }

        if ($status === null || $status === 'ativo') {
            return true;
        }

        return ! $this->isLastActiveAdmin($target);
    }

    public function viewAudit(User $user): bool
    {
        return $this->admin($user);
    }

    public function updatePassword(User $user, User $target): bool
    {
        return $this->internal($user) && $user->is($target);
    }

    public function resetOwnPassword(User $user): bool
    {
        return $this->internal($user);
    }

    private function isLastActiveAdmin(User $target): bool
    {
        return $target->isAdmin()
            && $target->ativo()
            && ! User::whereKeyNot($target->getKey())
                ->where('role', 'admin')
                ->where('status', 'ativo')
                ->exists();
    }
}
