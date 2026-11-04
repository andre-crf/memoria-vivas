<?php

namespace App\Auditing;

use App\Models\AuditEvent;
use App\Support\DataExibicao;
use Illuminate\Database\Eloquent\Builder;

final class AuditEventQuery
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<AuditEvent>
     */
    public function forFilters(array $filters, DataExibicao $datas): Builder
    {
        $query = AuditEvent::query();

        if ($startDate = $filters['data_inicio'] ?? null) {
            $query->where('occurred_at', '>=', $datas->inicioDoDia($startDate));
        }

        if ($endDate = $filters['data_fim'] ?? null) {
            $query->where('occurred_at', '<=', $datas->fimDoDia($endDate));
        }

        if (($filters['responsavel'] ?? null) === 'system') {
            $query->whereNull('actor_user_id');
        } elseif (filled($filters['responsavel'] ?? null)) {
            $query->where('actor_user_id', (int) $filters['responsavel']);
        }

        if ($action = $filters['acao'] ?? null) {
            $query->where('action', $action);
        }

        if ($entity = $filters['entidade'] ?? null) {
            $query->where('subject_type', $entity);
        }

        if (filled($filters['entidade_id'] ?? null)) {
            $query->where('subject_id', $filters['entidade_id']);
        }

        return $query
            ->orderByDesc('occurred_at')
            ->orderByDesc('id');
    }
}
