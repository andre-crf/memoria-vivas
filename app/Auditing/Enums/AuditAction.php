<?php

namespace App\Auditing\Enums;

enum AuditAction: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
    case Restored = 'restored';
    case ForceDeleted = 'force_deleted';
    case PasswordChanged = 'password_changed';
    case Activated = 'activated';
    case Deactivated = 'deactivated';
    case Uploaded = 'uploaded';
    case Replaced = 'replaced';
}
