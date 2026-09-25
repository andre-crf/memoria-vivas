<?php

namespace App\Auditing;

use App\Auditing\Enums\AuditSource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use LogicException;

final class AuditContextFactory
{
    private const CORRELATION_ID_ATTRIBUTE = 'audit_correlation_id';

    private const REQUEST_ID_ATTRIBUTE = 'audit_request_id';

    public function fromRequest(Request $request): AuditContext
    {
        $actor = $request->user();

        if (! $actor instanceof User) {
            throw new LogicException('Uma requisição auditável exige um usuário autenticado.');
        }

        $requestId = $this->uuidAttribute($request, self::REQUEST_ID_ATTRIBUTE);
        $correlationId = $this->uuidAttribute(
            $request,
            self::CORRELATION_ID_ATTRIBUTE,
            $requestId,
        );

        return AuditContext::forUser(
            user: $actor,
            source: AuditSource::Web,
            requestId: $requestId,
            correlationId: $correlationId,
        );
    }

    private function uuidAttribute(Request $request, string $key, ?string $fallback = null): string
    {
        $existing = $request->attributes->get($key);

        if (is_string($existing) && Str::isUuid($existing)) {
            return $existing;
        }

        $value = $fallback ?? (string) Str::uuid();
        $request->attributes->set($key, $value);

        return $value;
    }
}
