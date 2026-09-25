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

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Criação',
            self::Updated => 'Alteração',
            self::Deleted => 'Exclusão',
            self::Restored => 'Restauração',
            self::ForceDeleted => 'Exclusão definitiva',
            self::PasswordChanged => 'Alteração de senha',
            self::Activated => 'Ativação',
            self::Deactivated => 'Inativação',
            self::Uploaded => 'Upload',
            self::Replaced => 'Substituição',
        };
    }
}
