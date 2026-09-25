<x-layouts.admin title="Evento de auditoria #{{ $evento->id }} | Memórias Vivas">
    <div class="mx-auto max-w-7xl px-6 py-8">
        <section class="mb-6">
            <a
                href="{{ route('admin.auditoria.index', $navigationQuery) }}"
                class="text-sm font-semibold text-[#173F35] hover:text-[#0f2b24]"
            >
                Voltar para auditoria
            </a>
            <p class="mt-4 text-sm font-medium text-[#6B5E2E]">Evento de auditoria #{{ $evento->id }}</p>
            <div class="mt-2 flex flex-wrap items-center gap-3">
                <h1 class="text-3xl font-semibold text-stone-950">{{ $evento->action->label() }}</h1>
                <span class="inline-flex rounded-full bg-[#E8E2C9] px-2.5 py-1 text-xs font-semibold text-[#4A3F18]">
                    {{ $evento->subject_type->label() }}
                </span>
            </div>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-stone-600">
                Contexto histórico e valores registrados no momento da operação.
            </p>
        </section>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(300px,1fr)]">
            <div class="space-y-6">
                <section class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm" aria-labelledby="audit-differences-title">
                    <div class="border-b border-stone-200 px-6 py-5">
                        <h2 id="audit-differences-title" class="text-xl font-semibold text-stone-950">Alterações registradas</h2>
                        <p class="mt-1 text-sm text-stone-600">Comparação baseada exclusivamente no snapshot histórico do evento.</p>
                    </div>

                    @if ($differences === [])
                        <div class="px-6 py-10 text-center">
                            <p class="font-semibold text-stone-800">Este evento não possui valores anteriores ou novos.</p>
                            <p class="mt-2 text-sm leading-6 text-stone-600">
                                Algumas operações, como alterações de senha, registram somente a ocorrência por segurança.
                            </p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-stone-200">
                                <thead class="bg-stone-100">
                                    <tr>
                                        <th scope="col" class="min-w-48 px-5 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Campo</th>
                                        <th scope="col" class="min-w-72 px-5 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Antes</th>
                                        <th scope="col" class="min-w-72 px-5 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Depois</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-stone-200 bg-white">
                                    @foreach ($differences as $difference)
                                        <tr id="diferenca-{{ $difference['key'] }}" class="align-top">
                                            <th scope="row" class="px-5 py-4 text-left">
                                                <p class="text-sm font-semibold text-stone-950">{{ $difference['label'] }}</p>
                                                <p class="mt-1 font-mono text-xs text-stone-500">{{ $difference['key'] }}</p>
                                            </th>
                                            <td class="whitespace-pre-line break-words px-5 py-4 text-sm leading-6 text-stone-700">{{ $difference['before'] }}</td>
                                            <td class="whitespace-pre-line break-words px-5 py-4 text-sm leading-6 text-stone-700">{{ $difference['after'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>

                <section class="rounded-lg border border-stone-200 bg-white p-6 shadow-sm" aria-labelledby="audit-metadata-title">
                    <h2 id="audit-metadata-title" class="text-xl font-semibold text-stone-950">Contexto da operação</h2>
                    <p class="mt-1 text-sm text-stone-600">Metadados técnicos permitidos e consequências conhecidas.</p>

                    @if ($metadataRows === [])
                        <p class="mt-5 text-sm text-stone-600">Nenhum metadado adicional foi registrado.</p>
                    @else
                        <dl class="mt-5 grid gap-5 md:grid-cols-2">
                            @foreach ($metadataRows as $metadata)
                                <div class="min-w-0 rounded-md bg-stone-50 p-4">
                                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">
                                        {{ $metadata['label'] }}
                                    </dt>
                                    <dd class="mt-2 whitespace-pre-line break-words text-sm leading-6 text-stone-700">{{ $metadata['value'] }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    @endif
                </section>

                @if ($correlatedEvents->isNotEmpty())
                    <section class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm" aria-labelledby="audit-correlation-title">
                        <div class="border-b border-stone-200 px-6 py-5">
                            <h2 id="audit-correlation-title" class="text-xl font-semibold text-stone-950">Eventos correlacionados</h2>
                            <p class="mt-1 text-sm text-stone-600">Outras alterações que fizeram parte da mesma operação.</p>
                        </div>

                        <ul class="divide-y divide-stone-200">
                            @foreach ($correlatedEvents as $correlatedEvent)
                                <li id="evento-correlacionado-{{ $correlatedEvent->id }}" class="flex flex-col justify-between gap-3 px-6 py-4 sm:flex-row sm:items-center">
                                    <div>
                                        <p class="text-sm font-semibold text-stone-950">
                                            {{ $correlatedEvent->action->label() }} · {{ $correlatedEvent->subject_label ?: 'Sem identificação' }}
                                        </p>
                                        <p class="mt-1 text-xs text-stone-500">
                                            {{ $correlatedEvent->occurred_at->format('d/m/Y H:i:s') }} · {{ $correlatedEvent->subject_type->label() }} #{{ $correlatedEvent->subject_id }}
                                        </p>
                                    </div>
                                    <a
                                        href="{{ route('admin.auditoria.show', array_merge(['evento' => $correlatedEvent], $navigationQuery)) }}"
                                        class="shrink-0 text-sm font-semibold text-[#173F35] hover:text-[#0f2b24]"
                                    >
                                        Ver evento
                                    </a>
                                </li>
                            @endforeach
                        </ul>

                        @if ($correlatedEvents->hasPages())
                            <div class="border-t border-stone-200 px-5 py-4">
                                {{ $correlatedEvents->links() }}
                            </div>
                        @endif
                    </section>
                @endif
            </div>

            <aside class="space-y-6">
                <section class="rounded-lg border border-stone-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-semibold text-stone-950">Evento</h2>
                    <dl class="mt-5 space-y-5">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Data e hora</dt>
                            <dd class="mt-1 text-sm text-stone-700">{{ $evento->occurred_at->format('d/m/Y H:i:s') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Origem</dt>
                            <dd class="mt-1 text-sm text-stone-700">{{ $evento->source->label() }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Responsável no momento da ação</dt>
                            <dd class="mt-1 text-sm font-semibold text-stone-800">{{ $evento->actor_name ?: 'Sistema/sem usuário' }}</dd>
                            @if ($evento->actor_user_id)
                                <dd class="mt-1 text-xs text-stone-500">{{ $presenter->roleLabel($evento->actor_role) }} · Usuário #{{ $evento->actor_user_id }}</dd>
                            @endif
                        </div>
                    </dl>
                </section>

                <section class="rounded-lg border border-stone-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-semibold text-stone-950">Entidade afetada</h2>
                    <dl class="mt-5 space-y-5">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Identificação histórica</dt>
                            <dd class="mt-1 break-words text-sm font-semibold text-stone-800">{{ $evento->subject_label ?: 'Sem identificação' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Tipo e ID</dt>
                            <dd class="mt-1 text-sm text-stone-700">{{ $evento->subject_type->label() }} · #{{ $evento->subject_id }}</dd>
                            <dd class="mt-1 font-mono text-xs text-stone-500">{{ $evento->subject_type->value }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="rounded-lg border border-stone-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-semibold text-stone-950">Identificadores técnicos</h2>
                    <dl class="mt-5 space-y-5">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">ID do evento</dt>
                            <dd class="mt-1 font-mono text-xs text-stone-700">{{ $evento->id }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Request ID</dt>
                            <dd class="mt-1 break-all font-mono text-xs text-stone-700">{{ $evento->request_id }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Correlation ID</dt>
                            <dd class="mt-1 break-all font-mono text-xs text-stone-700">{{ $evento->correlation_id }}</dd>
                        </div>
                    </dl>
                </section>
            </aside>
        </div>
    </div>
</x-layouts.admin>
