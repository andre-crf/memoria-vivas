<x-layouts.admin title="Auditoria | Memórias Vivas">
    <div class="mx-auto max-w-7xl px-6 py-8">
        <section class="mb-6">
            <p class="text-sm font-medium text-[#6B5E2E]">Administração</p>
            <h1 class="mt-2 text-3xl font-semibold text-stone-950">Auditoria do sistema</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-stone-600">
                Consulte o histórico imutável das operações realizadas no sistema.
            </p>
        </section>

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

            <form method="GET" action="{{ route('admin.auditoria.index') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div>
                    <label for="data_inicio" class="block text-sm font-medium text-stone-700">Data inicial</label>
                    <input
                        id="data_inicio"
                        name="data_inicio"
                        type="date"
                        value="{{ request('data_inicio') }}"
                        class="mt-1 block w-full rounded-md border-stone-300 text-sm shadow-sm focus:border-[#173F35] focus:ring-[#173F35]"
                    >
                </div>

                <div>
                    <label for="data_fim" class="block text-sm font-medium text-stone-700">Data final</label>
                    <input
                        id="data_fim"
                        name="data_fim"
                        type="date"
                        value="{{ request('data_fim') }}"
                        class="mt-1 block w-full rounded-md border-stone-300 text-sm shadow-sm focus:border-[#173F35] focus:ring-[#173F35]"
                    >
                </div>

                <div>
                    <label for="responsavel" class="block text-sm font-medium text-stone-700">Responsável</label>
                    <select
                        id="responsavel"
                        name="responsavel"
                        class="mt-1 block w-full rounded-md border-stone-300 text-sm shadow-sm focus:border-[#173F35] focus:ring-[#173F35]"
                    >
                        <option value="">Todos os responsáveis</option>
                        <option value="system" @selected(request('responsavel') === 'system')>Sistema/sem usuário</option>
                        @foreach ($usuarios as $usuario)
                            <option value="{{ $usuario->id }}" @selected((string) request('responsavel') === (string) $usuario->id)>
                                {{ $usuario->nome }}{{ $usuario->status === 'inativo' ? ' (inativo)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="acao" class="block text-sm font-medium text-stone-700">Ação</label>
                    <select
                        id="acao"
                        name="acao"
                        class="mt-1 block w-full rounded-md border-stone-300 text-sm shadow-sm focus:border-[#173F35] focus:ring-[#173F35]"
                    >
                        <option value="">Todas as ações</option>
                        @foreach ($acoes as $acao)
                            <option value="{{ $acao->value }}" @selected(request('acao') === $acao->value)>{{ $acao->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="entidade" class="block text-sm font-medium text-stone-700">Entidade</label>
                    <select
                        id="entidade"
                        name="entidade"
                        class="mt-1 block w-full rounded-md border-stone-300 text-sm shadow-sm focus:border-[#173F35] focus:ring-[#173F35]"
                    >
                        <option value="">Todas as entidades</option>
                        @foreach ($entidades as $entidade)
                            <option value="{{ $entidade->value }}" @selected(request('entidade') === $entidade->value)>{{ $entidade->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="entidade_id" class="block text-sm font-medium text-stone-700">ID da entidade</label>
                    <input
                        id="entidade_id"
                        name="entidade_id"
                        type="text"
                        maxlength="191"
                        value="{{ request('entidade_id') }}"
                        placeholder="Ex.: 42"
                        class="mt-1 block w-full rounded-md border-stone-300 text-sm shadow-sm focus:border-[#173F35] focus:ring-[#173F35]"
                    >
                    <p class="mt-1 text-xs text-stone-500">Selecione também o tipo da entidade.</p>
                </div>

                <div class="flex flex-wrap items-center gap-2 md:col-span-2 xl:col-span-3">
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-md bg-[#173F35] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#0f2b24] focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2"
                    >
                        Filtrar
                    </button>
                    <a
                        href="{{ route('admin.auditoria.index') }}"
                        class="inline-flex items-center justify-center rounded-md border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:bg-stone-100 focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2"
                    >
                        Limpar filtros
                    </a>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm">
            @if (! $hasAnyEvents)
                <div class="px-6 py-16 text-center">
                    <h2 class="text-xl font-semibold text-stone-950">Nenhum evento de auditoria registrado</h2>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-stone-600">
                        Os eventos aparecerão aqui conforme operações auditáveis forem realizadas.
                    </p>
                </div>
            @elseif ($eventos->isEmpty())
                <div class="px-6 py-16 text-center">
                    <h2 class="text-xl font-semibold text-stone-950">Nenhum evento encontrado</h2>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-stone-600">
                        Ajuste ou remova os filtros para ampliar os resultados da consulta.
                    </p>
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
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-200 bg-white">
                            @foreach ($eventos as $evento)
                                <tr id="evento-auditoria-{{ $evento->id }}" class="hover:bg-stone-50">
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-stone-700">
                                        {{ $evento->occurred_at->format('d/m/Y H:i:s') }}
                                    </td>
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
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <span class="inline-flex rounded-full bg-[#E8E2C9] px-2.5 py-1 text-xs font-medium text-[#4A3F18]">
                                            {{ $evento->action->label() }}
                                        </span>
                                    </td>
                                    <td class="min-w-64 px-5 py-4">
                                        <p class="text-sm font-semibold text-stone-950">{{ $evento->subject_label ?: 'Sem identificação' }}</p>
                                        <p class="mt-1 text-xs text-stone-500">{{ $evento->subject_type->label() }} · #{{ $evento->subject_id }}</p>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-stone-200 px-5 py-4">
                    {{ $eventos->links() }}
                </div>
            @endif
        </section>
    </div>
</x-layouts.admin>
