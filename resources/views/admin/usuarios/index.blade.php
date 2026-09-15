<x-layouts.admin title="Usuários | Memórias Vivas">
    <div class="mx-auto max-w-7xl px-6 py-8">
        <section class="mb-6 flex flex-col justify-between gap-4 md:flex-row md:items-end">
            <div>
                <p class="text-sm font-medium text-[#6B5E2E]">Administração</p>
                <h2 class="mt-1 text-2xl font-semibold text-stone-950">Usuários</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-stone-600">Gerencie as contas com acesso à área administrativa.</p>
            </div>
            <a href="{{ route('admin.usuarios.create') }}" class="inline-flex items-center justify-center rounded-md bg-[#173F35] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#0f2b24] focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2">Novo usuário</a>
        </section>

        @if (session('success'))
            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">{{ session('success') }}</div>
        @endif

        <section class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm">
            @if ($usuarios->isEmpty())
                <div class="px-6 py-16 text-center">
                    <h3 class="text-base font-semibold text-stone-950">Nenhum usuário cadastrado</h3>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-stone-600">Quando houver usuários cadastrados, eles aparecerão nesta listagem.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-stone-200">
                        <thead class="bg-stone-100">
                            <tr>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Nome</th>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">E-mail</th>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Perfil</th>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Situação</th>
                                <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-[0.12em] text-stone-600">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-200 bg-white">
                            @foreach ($usuarios as $usuario)
                                <tr id="usuario-{{ $usuario->id }}" class="hover:bg-stone-50">
                                    <td class="max-w-sm px-5 py-4">
                                        <p class="truncate text-sm font-semibold text-stone-950">{{ $usuario->nome }}</p>
                                        <p class="mt-1 text-xs text-stone-500">#{{ $usuario->id }}</p>
                                    </td>
                                    <td class="px-5 py-4 text-sm text-stone-700">{{ $usuario->email }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-stone-700">{{ $usuario->isAdmin() ? 'Administrador' : 'Operador' }}</td>
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $usuario->ativo() ? 'bg-[#E8E2C9] text-[#4A3F18]' : 'bg-stone-100 text-stone-700' }}">
                                            {{ $usuario->ativo() ? 'Ativo' : 'Inativo' }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <a href="{{ route('admin.usuarios.edit', $usuario) }}" class="rounded-md border border-stone-300 px-3 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-100 focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2">Editar</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</x-layouts.admin>
