<div>
    @if ($errors->any())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
            <p class="font-semibold">Não foi possível aplicar os filtros.</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="mb-6 rounded-lg border border-stone-200 bg-white p-5 shadow-sm" aria-labelledby="audit-filter-title">
        <div class="mb-4">
            <h2 id="audit-filter-title" class="text-base font-semibold text-stone-950">Filtros</h2>
            <p class="mt-1 text-sm text-stone-600">Combine os campos para localizar eventos específicos.</p>
        </div>

        <form wire:submit="applyFilters" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <input type="hidden" wire:model="fuso" value="{{ $fuso }}" data-fuso-usuario>

            <div>
                <label for="data_inicio" class="block text-sm font-medium text-stone-700">Data inicial</label>
                <input id="data_inicio" type="date" wire:model.live="data_inicio" class="mt-1 block w-full rounded-md border-stone-300 text-sm shadow-sm focus:border-[#173F35] focus:ring-[#173F35]">
            </div>

            <div>
                <label for="data_fim" class="block text-sm font-medium text-stone-700">Data final</label>
                <input id="data_fim" type="date" wire:model.live="data_fim" class="mt-1 block w-full rounded-md border-stone-300 text-sm shadow-sm focus:border-[#173F35] focus:ring-[#173F35]">
            </div>

            <div>
                <label for="responsavel" class="block text-sm font-medium text-stone-700">Responsável</label>
                <select id="responsavel" wire:model.live="responsavel" class="mt-1 block w-full rounded-md border-stone-300 text-sm shadow-sm focus:border-[#173F35] focus:ring-[#173F35]">
                    <option value="">Todos os responsáveis</option>
                    <option value="system">Sistema/sem usuário</option>
                    @foreach ($usuarios as $usuario)
                        <option value="{{ $usuario->id }}">{{ $usuario->nome }}{{ $usuario->status === 'inativo' ? ' (inativo)' : '' }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="acao" class="block text-sm font-medium text-stone-700">Ação</label>
                <select id="acao" wire:model.live="acao" class="mt-1 block w-full rounded-md border-stone-300 text-sm shadow-sm focus:border-[#173F35] focus:ring-[#173F35]">
                    <option value="">Todas as ações</option>
                    @foreach ($acoes as $acaoOption)
                        <option value="{{ $acaoOption->value }}">{{ $acaoOption->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="entidade" class="block text-sm font-medium text-stone-700">Entidade</label>
                <select id="entidade" wire:model.live="entidade" class="mt-1 block w-full rounded-md border-stone-300 text-sm shadow-sm focus:border-[#173F35] focus:ring-[#173F35]">
                    <option value="">Todas as entidades</option>
                    @foreach ($entidades as $entidadeOption)
                        <option value="{{ $entidadeOption->value }}">{{ $entidadeOption->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="entidade_id" class="block text-sm font-medium text-stone-700">ID da entidade</label>
                <input id="entidade_id" type="text" maxlength="191" wire:model.live.debounce.300ms="entidade_id" placeholder="Ex.: 42" class="mt-1 block w-full rounded-md border-stone-300 text-sm shadow-sm focus:border-[#173F35] focus:ring-[#173F35]">
                <p class="mt-1 text-xs text-stone-500">Selecione também o tipo da entidade.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2 md:col-span-2 xl:col-span-3">
                <button type="submit" class="inline-flex items-center justify-center rounded-md bg-[#173F35] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#0f2b24] focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2">Filtrar</button>
                <button type="button" wire:click="clearFilters" class="inline-flex items-center justify-center rounded-md border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:bg-stone-100 focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2">Limpar filtros</button>
                <p class="text-xs text-stone-500">
                    Período interpretado no fuso <span class="font-medium text-stone-700">{{ $fusoExibicao }}</span>.
                </p>
            </div>
        </form>
    </section>

    <section class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm" wire:loading.class="opacity-60">
        @if (! $hasAnyEvents)
            <div class="px-6 py-16 text-center">
                <h2 class="text-xl font-semibold text-stone-950">Nenhum evento de auditoria registrado</h2>
                <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-stone-600">Os eventos aparecerão aqui conforme operações auditáveis forem realizadas.</p>
            </div>
        @elseif ($eventos->isEmpty())
            <div class="px-6 py-16 text-center">
                <h2 class="text-xl font-semibold text-stone-950">Nenhum evento encontrado</h2>
                <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-stone-600">Ajuste ou remova os filtros para ampliar os resultados da consulta.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-stone-200">
                    <thead class="bg-stone-100">
                        <tr>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Data e hora</th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Responsável</th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Ação</th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Entidade afetada</th>
                            <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Detalhes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-200 bg-white">
                        @foreach ($eventos as $evento)
                            <tr id="evento-auditoria-{{ $evento->id }}" wire:key="audit-event-{{ $evento->id }}" class="hover:bg-stone-50">
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-stone-700"><x-data-hora :valor="$evento->occurred_at" /></td>
                                <td class="min-w-52 px-5 py-4">
                                    <p class="text-sm font-semibold text-stone-950">{{ $evento->actor_name ?: 'Sistema' }}</p>
                                    <p class="mt-1 text-xs text-stone-500">
                                        @if ($evento->actor_user_id)
                                            {{ $evento->actor_role === 'admin' ? 'Administrador' : 'Operador' }} · #{{ $evento->actor_user_id }}
                                        @else
                                            Sem usuário responsável
                                        @endif
                                    </p>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4"><span class="inline-flex rounded-full bg-[#E8E2C9] px-2.5 py-1 text-xs font-medium text-[#4A3F18]">{{ $evento->action->label() }}</span></td>
                                <td class="min-w-64 px-5 py-4">
                                    <p class="text-sm font-semibold text-stone-950">{{ $evento->subject_label ?: 'Sem identificação' }}</p>
                                    <p class="mt-1 text-xs text-stone-500">{{ $evento->subject_type->label() }} · #{{ $evento->subject_id }}</p>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-right">
                                    <a href="{{ route('admin.auditoria.show', array_merge(['evento' => $evento], $navigationQuery)) }}" class="text-sm font-semibold text-[#173F35] hover:text-[#0f2b24]">Ver detalhes</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-stone-200 px-5 py-4">{{ $eventos->links() }}</div>
        @endif
    </section>
</div>
