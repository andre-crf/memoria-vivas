<?php

namespace App\Http\Controllers\Admin;

use App\Auditing\AuditEventPresenter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexAuditoriaRequest;
use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AuditoriaController extends Controller
{
    public function index(IndexAuditoriaRequest $request): View
    {
        Gate::authorize('viewAudit', User::class);
        $request->validated();

        return view('admin.auditoria.index');
    }

    public function show(
        IndexAuditoriaRequest $request,
        AuditEvent $evento,
        AuditEventPresenter $presenter,
    ): View {
        Gate::authorize('viewAudit', User::class);

        $navigationQuery = collect($request->validated())
            ->only(IndexAuditoriaRequest::FILTERS)
            ->all();
        $correlatedEvents = AuditEvent::query()
            ->where('correlation_id', $evento->correlation_id)
            ->whereKeyNot($evento->id)
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'correlacionados')
            ->withQueryString();

        return view('admin.auditoria.show', [
            'evento' => $evento,
            'differences' => $presenter->differences($evento),
            'metadataRows' => $presenter->metadata($evento),
            'correlatedEvents' => $correlatedEvents,
            'navigationQuery' => $navigationQuery,
            'presenter' => $presenter,
        ]);
    }
}
