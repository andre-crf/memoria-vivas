<?php

namespace App\Http\Controllers\Admin;

use App\Auditing\Enums\AuditAction;
use App\Auditing\Enums\AuditEntity;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexAuditoriaRequest;
use App\Models\AuditEvent;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AuditoriaController extends Controller
{
    public function index(IndexAuditoriaRequest $request): View
    {
        Gate::authorize('viewAudit', User::class);

        $filters = $request->validated();
        $timezone = config('app.timezone');
        $query = AuditEvent::query();

        if ($startDate = $filters['data_inicio'] ?? null) {
            $query->where(
                'occurred_at',
                '>=',
                CarbonImmutable::createFromFormat('Y-m-d', $startDate, $timezone)->startOfDay(),
            );
        }

        if ($endDate = $filters['data_fim'] ?? null) {
            $query->where(
                'occurred_at',
                '<=',
                CarbonImmutable::createFromFormat('Y-m-d', $endDate, $timezone)->endOfDay(),
            );
        }

        if (($filters['responsavel'] ?? null) === 'system') {
            $query->whereNull('actor_user_id');
        } elseif (isset($filters['responsavel'])) {
            $query->where('actor_user_id', (int) $filters['responsavel']);
        }

        if ($action = $filters['acao'] ?? null) {
            $query->where('action', $action);
        }

        if ($entity = $filters['entidade'] ?? null) {
            $query->where('subject_type', $entity);
        }

        if (isset($filters['entidade_id'])) {
            $query->where('subject_id', $filters['entidade_id']);
        }

        return view('admin.auditoria.index', [
            'eventos' => $query
                ->orderByDesc('occurred_at')
                ->orderByDesc('id')
                ->paginate(25)
                ->withQueryString(),
            'hasAnyEvents' => AuditEvent::query()->exists(),
            'usuarios' => User::query()->orderBy('nome')->get(['id', 'nome', 'status']),
            'acoes' => AuditAction::cases(),
            'entidades' => AuditEntity::cases(),
        ]);
    }
}
