<?php

namespace App\Auditing\Enums;

enum AuditSource: string
{
    case Web = 'web';
    case Console = 'console';
    case Job = 'job';
    case System = 'system';

    public function label(): string
    {
        return match ($this) {
            self::Web => 'Interface web',
            self::Console => 'Console',
            self::Job => 'Processamento em segundo plano',
            self::System => 'Sistema',
        };
    }
}
