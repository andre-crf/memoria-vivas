<div>
    @if ($errors->any())
        <x-ui.alert tone="danger" title="Não foi possível aplicar os filtros." class="mb-6">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-ui.alert>
    @endif

    <section class="mb-6 rounded-lg border border-line bg-white p-5 shadow-card" aria-labelledby="audit-filter-title">
        <div class="mb-4">
            <h2 id="audit-filter-title" class="text-base font-semibold text-fg">Filtros</h2>
            <p class="mt-1 text-sm text-fg-muted">Combine os campos para localizar eventos específicos.</p>
        </div>

        <form wire:submit="applyFilters" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <input type="hidden" wire:model="fuso" value="{{ $fuso }}" data-fuso-usuario>

            <x-catalog.label for="data_inicio" text="Data inicial">
                <x-catalog.control id="data_inicio" type="date" wire:model.live="data_inicio" />
            </x-catalog.label>

            <x-catalog.label for="data_fim" text="Data final">
                <x-catalog.control id="data_fim" type="date" wire:model.live="data_fim" />
            </x-catalog.label>

            <x-catalog.label for="responsavel" text="Responsável">
                <x-catalog.control as="select" id="responsavel" wire:model.live="responsavel">
                    <option value="">Todos os responsáveis</option>
                    <option value="system">Sistema/sem usuário</option>
                    @foreach ($usuarios as $usuario)
                        <option value="{{ $usuario->id }}">{{ $usuario->nome }}{{ $usuario->status === 'inativo' ? ' (inativo)' : '' }}</option>
                    @endforeach
                </x-catalog.control>
            </x-catalog.label>

            <x-catalog.label for="acao" text="Ação">
                <x-catalog.control as="select" id="acao" wire:model.live="acao">
                    <option value="">Todas as ações</option>
                    @foreach ($acoes as $acaoOption)
                        <option value="{{ $acaoOption->value }}">{{ $acaoOption->label() }}</option>
                    @endforeach
                </x-catalog.control>
            </x-catalog.label>

            <x-catalog.label for="entidade" text="Entidade">
                <x-catalog.control as="select" id="entidade" wire:model.live="entidade">
                    <option value="">Todas as entidades</option>
                    @foreach ($entidades as $entidadeOption)
                        <option value="{{ $entidadeOption->value }}">{{ $entidadeOption->label() }}</option>
                    @endforeach
                </x-catalog.control>
            </x-catalog.label>

            <x-catalog.label for="entidade_id" text="ID da entidade">
                <x-catalog.control id="entidade_id" type="text" maxlength="191" wire:model.live.debounce.300ms="entidade_id" placeholder="Ex.: 42" aria-describedby="entidade_id-dica" />
                <span id="entidade_id-dica" class="text-caption text-fg-subtle">Selecione também o tipo da entidade.</span>
            </x-catalog.label>

            <div class="flex flex-wrap items-center gap-2 md:col-span-2 xl:col-span-3">
                <x-catalog.button type="submit" icon="search">Filtrar</x-catalog.button>
                <x-catalog.button variant="secondary" wire:click="clearFilters">Limpar filtros</x-catalog.button>
                <p class="text-xs text-fg-subtle">
                    Período interpretado no fuso <span class="font-medium text-ink-700">{{ $fusoExibicao }}</span>.
                </p>
            </div>
        </form>
    </section>

    <x-admin.table :paginator="$eventos" :vazia="! $hasAnyEvents || $eventos->isEmpty()" wire:loading.class="opacity-60">
        <x-slot:vazio>
            @if (! $hasAnyEvents)
                <x-ui.empty-state icon="history" title="Nenhum evento de auditoria registrado" description="Os eventos aparecerão aqui conforme operações auditáveis forem realizadas." />
            @else
                <x-ui.empty-state icon="search-x" title="Nenhum evento encontrado" description="Ajuste ou remova os filtros para ampliar os resultados da consulta.">
                    <x-slot:action>
                        <x-catalog.button variant="secondary" size="sm" wire:click="clearFilters">Limpar filtros</x-catalog.button>
                    </x-slot:action>
                </x-ui.empty-state>
            @endif
        </x-slot:vazio>

        <thead>
            <tr>
                <th scope="col">Data e hora</th>
                <th scope="col">Responsável</th>
                <th scope="col">Ação</th>
                <th scope="col">Entidade afetada</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($eventos as $evento)
                <tr id="evento-auditoria-{{ $evento->id }}" wire:key="audit-event-{{ $evento->id }}" class="relative">
                    <td class="whitespace-nowrap text-ink-700"><x-data-hora :valor="$evento->occurred_at" /></td>
                    <td class="min-w-52">
                        <p class="font-semibold text-fg">{{ $evento->actor_name ?: 'Sistema' }}</p>
                        <p class="mt-1 text-xs text-fg-subtle">
                            @if ($evento->actor_user_id)
                                {{ $evento->actor_role === 'admin' ? 'Administrador' : 'Operador' }} · #{{ $evento->actor_user_id }}
                            @else
                                Sem usuário responsável
                            @endif
                        </p>
                    </td>
                    <td class="whitespace-nowrap"><x-ui.badge tone="heritage">{{ $evento->action->label() }}</x-ui.badge></td>
                    <td class="min-w-64">
                        <p class="font-semibold text-fg"><x-admin.link-linha :href="route('admin.auditoria.show', array_merge(['evento' => $evento], $navigationQuery))">{{ $evento->subject_label ?: 'Sem identificação' }}</x-admin.link-linha></p>
                        <p class="mt-1 text-xs text-fg-subtle">{{ $evento->subject_type->label() }} · #{{ $evento->subject_id }}</p>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </x-admin.table>
</div>
