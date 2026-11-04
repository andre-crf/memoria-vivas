<?php

namespace App\Models;

use App\Auditing\Enums\AuditAction;
use App\Auditing\Enums\AuditEntity;
use App\Auditing\Enums\AuditSource;
use App\Auditing\ReadOnlyAuditEventBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Builder as QueryBuilder;
use LogicException;

final class AuditEvent extends Model
{
    public const TABLE = 'audit_events';

    protected $table = self::TABLE;

    protected $guarded = ['*'];

    public $timestamps = false;

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function save(array $options = [])
    {
        throw new LogicException('Eventos de auditoria somente podem ser gravados pelo AuditRecorder.');
    }

    public function delete()
    {
        throw new LogicException('Eventos de auditoria são imutáveis.');
    }

    public function newEloquentBuilder($query): Builder
    {
        /** @var QueryBuilder $query */
        return new ReadOnlyAuditEventBuilder($query);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => AuditAction::class,
            'subject_type' => AuditEntity::class,
            'old_values' => 'array',
            'new_values' => 'array',
            'metadata' => 'array',
            'source' => AuditSource::class,
            'occurred_at' => 'immutable_datetime',
        ];
    }
}
