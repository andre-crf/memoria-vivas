<?php

namespace App\Auditing\Enums;

enum AuditSource: string
{
    case Web = 'web';
    case Console = 'console';
    case Job = 'job';
    case System = 'system';
}
