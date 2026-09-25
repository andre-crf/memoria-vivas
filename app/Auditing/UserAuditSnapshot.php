<?php

namespace App\Auditing;

use App\Models\User;

final class UserAuditSnapshot
{
    /** @var list<string> */
    public const FIELDS = [
        'nome',
        'email',
        'role',
        'status',
    ];

    public static function capture(User $user): AuditSnapshot
    {
        return AuditSnapshot::fromModel($user, self::FIELDS);
    }
}
