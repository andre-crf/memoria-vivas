<x-layouts.admin title="Evento de auditoria #{{ $evento->id }} | Memórias Vivas">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <x-admin.page-header
            class="mb-6"
            :eyebrow="'Evento de auditoria #'.$evento->id"
            :title="$evento->action->label()"
            description="Contexto histórico e valores registrados no momento da operação."
            :back-href="route('admin.auditoria.index', $navigationQuery)"
            back-label="Voltar para auditoria"
        >
            <x-ui.badge tone="heritage">{{ $evento->subject_type->label() }}</x-ui.badge>
        </x-admin.page-header>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(300px,1fr)]">
            <div class="space-y-6">
                <x-catalog.section flush title="Alterações registradas" title-id="audit-differences-title" description="Comparação baseada exclusivamente no snapshot histórico do evento.">
                    @if ($differences === [])
                        <x-ui.empty-state
                            heading="h3"
                            title="Este evento não possui valores anteriores ou novos."
                            description="Algumas operações, como alterações de senha, registram somente a ocorrência por segurança."
                        />
                    @else
                        <div class="overflow-x-auto">
                            <table class="admin-table min-w-full text-left text-sm">
                                <thead>
                                    <tr>
                                        <th scope="col" class="min-w-48">Campo</th>
                                        <th scope="col" class="min-w-72">Antes</th>
                                        <th scope="col" class="min-w-72">Depois</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($differences as $difference)
                                        <tr id="diferenca-{{ $difference['key'] }}">
                                            <th scope="row" class="px-5 py-4 text-left">
                                                <p class="font-semibold text-fg">{{ $difference['label'] }}</p>
                                                <p class="mt-1 font-mono text-xs text-fg-subtle">{{ $difference['key'] }}</p>
                                            </th>
                                            <td class="whitespace-pre-line break-words leading-6 text-ink-700">{{ $difference['before'] }}</td>
                                            <td class="whitespace-pre-line break-words leading-6 text-ink-700">{{ $difference['after'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </x-catalog.section>

                <x-catalog.section title="Contexto da operação" title-id="audit-metadata-title" description="Metadados técnicos permitidos e consequências conhecidas.">
                    @if ($metadataRows === [])
                        <p class="text-sm text-fg-muted">Nenhum metadado adicional foi registrado.</p>
                    @else
                        <dl class="grid gap-3 md:grid-cols-2">
                            @foreach ($metadataRows as $metadata)
                                <x-admin.info-block :label="$metadata['label']">
                                    <span class="block whitespace-pre-line break-words">{{ $metadata['value'] }}</span>
                                </x-admin.info-block>
                            @endforeach
                        </dl>
                    @endif
                </x-catalog.section>

                @if ($correlatedEvents->isNotEmpty())
                    <x-catalog.section flush title="Eventos correlacionados" title-id="audit-correlation-title" description="Outras alterações que fizeram parte da mesma operação.">
                        <ul class="divide-y divide-line">
                            @foreach ($correlatedEvents as $correlatedEvent)
                                <li id="evento-correlacionado-{{ $correlatedEvent->id }}" class="flex flex-col justify-between gap-3 px-5 py-4 sm:flex-row sm:items-center sm:px-6">
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-fg">
                                            {{ $correlatedEvent->action->label() }} · {{ $correlatedEvent->subject_label ?: 'Sem identificação' }}
                                        </p>
                                        <p class="mt-1 text-xs text-fg-subtle">
                                            <x-data-hora :valor="$correlatedEvent->occurred_at" /> · {{ $correlatedEvent->subject_type->label() }} #{{ $correlatedEvent->subject_id }}
                                        </p>
                                    </div>
                                    <a
                                        href="{{ route('admin.auditoria.show', array_merge(['evento' => $correlatedEvent], $navigationQuery)) }}"
                                        class="shrink-0 self-start rounded text-sm font-semibold text-institutional hover:text-institutional-strong hover:underline hover:underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-institutional sm:self-auto"
                                    >
                                        Ver evento
                                    </a>
                                </li>
                            @endforeach
                        </ul>

                        @if ($correlatedEvents->hasPages())
                            <div class="border-t border-line px-5 py-4">
                                {{ $correlatedEvents->links() }}
                            </div>
                        @endif
                    </x-catalog.section>
                @endif
            </div>

            <aside class="space-y-6">
                <x-catalog.section title="Evento">
                    <dl class="grid gap-3">
                        <x-admin.info-block label="Data e hora"><x-data-hora :valor="$evento->occurred_at" /></x-admin.info-block>
                        <x-admin.info-block label="Origem">{{ $evento->source->label() }}</x-admin.info-block>
                        <x-admin.info-block label="Responsável no momento da ação">
                            <span class="block font-semibold">{{ $evento->actor_name ?: 'Sistema/sem usuário' }}</span>
                            @if ($evento->actor_user_id)
                                <span class="block text-xs text-fg-subtle">{{ $presenter->roleLabel($evento->actor_role) }} · Usuário #{{ $evento->actor_user_id }}</span>
                            @endif
                        </x-admin.info-block>
                    </dl>
                </x-catalog.section>

                <x-catalog.section title="Entidade afetada">
                    <dl class="grid gap-3">
                        <x-admin.info-block label="Identificação histórica">
                            <span class="block break-words font-semibold">{{ $evento->subject_label ?: 'Sem identificação' }}</span>
                        </x-admin.info-block>
                        <x-admin.info-block label="Tipo e ID">
                            <span class="block">{{ $evento->subject_type->label() }} · #{{ $evento->subject_id }}</span>
                            <span class="block font-mono text-xs text-fg-subtle">{{ $evento->subject_type->value }}</span>
                        </x-admin.info-block>
                    </dl>
                </x-catalog.section>

                <x-catalog.section title="Identificadores técnicos">
                    <dl class="grid gap-3">
                        <x-admin.info-block label="ID do evento"><span class="font-mono text-xs">{{ $evento->id }}</span></x-admin.info-block>
                        <x-admin.info-block label="Request ID"><span class="block break-all font-mono text-xs">{{ $evento->request_id }}</span></x-admin.info-block>
                        <x-admin.info-block label="Correlation ID"><span class="block break-all font-mono text-xs">{{ $evento->correlation_id }}</span></x-admin.info-block>
                    </dl>
                </x-catalog.section>
            </aside>
        </div>
    </div>
</x-layouts.admin>
