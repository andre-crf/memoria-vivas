<x-layouts.admin title="{{ $fotografia->titulo }} | Memórias Vivas" active="fotografias">
    <div class="mx-auto max-w-7xl">
        <section class="mb-6 flex flex-col justify-between gap-4 lg:flex-row lg:items-start">
            <div>
                <a
                    href="{{ route('admin.fotografias.index') }}"
                    class="text-sm font-semibold text-[#173F7A] hover:text-[#0E2A52]"
                >
                    Voltar para fotografias
                </a>
                <p class="mt-4 text-sm font-medium text-[#3F7E94]">Fotografia #{{ $fotografia->id }}</p>
                <h1 class="mt-2 max-w-4xl font-serif text-3xl font-semibold text-[#173F7A]">{{ $fotografia->titulo }}</h1>
                <div class="mt-3 flex flex-wrap gap-2">
                    <span class="inline-flex rounded-full bg-[#E8EEF6] px-2.5 py-1 text-xs font-semibold text-[#294B77]">
                        {{ $fotografia->statusLabel() }}
                    </span>
                    <span class="inline-flex rounded-full bg-[#DCEEEF] px-2.5 py-1 text-xs font-semibold text-[#287C7C]">
                        {{ $fotografia->visibilidade->label() }}
                    </span>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                @can('update', $fotografia)
                    <a
                        href="#edicao"
                        class="inline-flex items-center justify-center rounded-md border border-[#D8E2EF] px-4 py-2 text-sm font-semibold text-[#173F7A] transition hover:bg-[#F4F8FC] focus:outline-none focus:ring-2 focus:ring-[#173F7A] focus:ring-offset-2"
                    >
                        Editar
                    </a>
                @endcan

                @can('delete', $fotografia)
                    <form method="POST" action="{{ route('admin.fotografias.destroy', $fotografia) }}" onsubmit="return confirm('Excluir esta fotografia?')">
                        @csrf
                        @method('DELETE')
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-md border border-red-200 px-4 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-700 focus:ring-offset-2"
                        >
                            Excluir
                        </button>
                    </form>
                @endcan
            </div>
        </section>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(320px,1fr)]">
            <div class="space-y-6">
                <section id="dados-catalogacao" class="rounded-lg border border-[#D8E2EF] bg-white p-6 shadow-sm shadow-[#173F7A]/5">
                    <h2 class="font-serif text-2xl font-semibold text-[#173F7A]">Dados de catalogação</h2>

                    <dl class="mt-5 grid gap-5 md:grid-cols-2">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-[#55709B]">Título</dt>
                            <dd class="mt-1 text-sm text-[#173F7A]">{{ $fotografia->titulo }}</dd>
                        </div>

                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-[#55709B]">Data</dt>
                            <dd class="mt-1 text-sm text-[#173F7A]">{{ $fotografia->dataHistorica()->label() }}</dd>
                        </div>

                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-[#55709B]">Local atual</dt>
                            <dd class="mt-1 text-sm text-[#173F7A]">{{ $fotografia->local_atual ?: 'Não informado' }}</dd>
                        </div>

                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-[#55709B]">Local na época</dt>
                            <dd class="mt-1 text-sm text-[#173F7A]">{{ $fotografia->local_epoca ?: 'Não informado' }}</dd>
                        </div>

                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-[#55709B]">Evento relacionado</dt>
                            <dd class="mt-1 text-sm text-[#173F7A]">{{ $fotografia->evento ?: 'Não informado' }}</dd>
                        </div>

                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-[#55709B]">Cedente</dt>
                            <dd class="mt-1 text-sm text-[#173F7A]">{{ $fotografia->cedente ?: 'Não informado' }}</dd>
                        </div>

                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-[#55709B]">Estado de conservação</dt>
                            <dd class="mt-1 text-sm text-[#173F7A]">{{ $fotografia->estadoConservacaoLabel() }}</dd>
                        </div>

                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-[#55709B]">Precisão da data</dt>
                            <dd class="mt-1 text-sm text-[#173F7A]">{{ $fotografia->tipo_data->label() }}</dd>
                        </div>
                    </dl>

                    <div class="mt-5">
                        <h3 class="text-xs font-semibold uppercase tracking-[0.12em] text-[#55709B]">Legenda/descrição</h3>
                        <p class="mt-1 whitespace-pre-line text-sm leading-6 text-[#173F7A]">{{ $fotografia->legenda ?: 'Não informado' }}</p>
                    </div>
                </section>

                <section class="rounded-lg border border-[#D8E2EF] bg-white p-6 shadow-sm shadow-[#173F7A]/5">
                    <h2 class="font-serif text-2xl font-semibold text-[#173F7A]">Classificações</h2>

                    <div class="mt-5 grid gap-5 md:grid-cols-2">
                        <div>
                            <h3 class="text-xs font-semibold uppercase tracking-[0.12em] text-[#55709B]">Categorias</h3>
                            @if ($fotografia->categorias->isEmpty())
                                <p class="mt-2 text-sm text-[#55709B]">Nenhuma categoria vinculada.</p>
                            @else
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach ($fotografia->categorias as $categoria)
                                        <span class="rounded-full bg-[#E8EEF6] px-2.5 py-1 text-xs font-medium text-[#294B77]">{{ $categoria->titulo }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div>
                            <h3 class="text-xs font-semibold uppercase tracking-[0.12em] text-[#55709B]">Assuntos</h3>
                            @if ($fotografia->assuntos->isEmpty())
                                <p class="mt-2 text-sm text-[#55709B]">Nenhum assunto vinculado.</p>
                            @else
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach ($fotografia->assuntos as $assunto)
                                        <span class="rounded-full bg-[#E8EEF6] px-2.5 py-1 text-xs font-medium text-[#294B77]">{{ $assunto->titulo }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div>
                            <h3 class="text-xs font-semibold uppercase tracking-[0.12em] text-[#55709B]">Palavras-chave</h3>
                            @if ($fotografia->palavrasChave->isEmpty())
                                <p class="mt-2 text-sm text-[#55709B]">Nenhuma palavra-chave vinculada.</p>
                            @else
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach ($fotografia->palavrasChave as $palavraChave)
                                        <span class="rounded-full bg-[#E8EEF6] px-2.5 py-1 text-xs font-medium text-[#294B77]">{{ $palavraChave->termo }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div>
                            <h3 class="text-xs font-semibold uppercase tracking-[0.12em] text-[#55709B]">Coleções</h3>
                            @if ($fotografia->colecoes->isEmpty())
                                <p class="mt-2 text-sm text-[#55709B]">Nenhuma coleção vinculada.</p>
                            @else
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach ($fotografia->colecoes as $colecao)
                                        <span class="rounded-full bg-[#E8EEF6] px-2.5 py-1 text-xs font-medium text-[#294B77]">{{ $colecao->titulo }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="md:col-span-2">
                            <h3 class="text-xs font-semibold uppercase tracking-[0.12em] text-[#55709B]">Conjuntos contextuais</h3>
                            @if ($fotografia->conjuntosContextuais->isEmpty())
                                <p class="mt-2 text-sm text-[#55709B]">Nenhum conjunto contextual vinculado.</p>
                            @else
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach ($fotografia->conjuntosContextuais as $conjunto)
                                        <span class="rounded-full bg-[#E8EEF6] px-2.5 py-1 text-xs font-medium text-[#294B77]">
                                            {{ $conjunto->titulo }}@if ($conjunto->pivot->ordem !== null) · Ordem {{ $conjunto->pivot->ordem }} @endif
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </section>

                <section class="rounded-lg border border-[#D8E2EF] bg-white p-6 shadow-sm shadow-[#173F7A]/5">
                    <h2 class="font-serif text-2xl font-semibold text-[#173F7A]">Pessoas e autoria</h2>

                    <dl class="mt-5 grid gap-5 md:grid-cols-2">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-[#55709B]">Autor</dt>
                            <dd class="mt-1 text-sm text-[#173F7A]">{{ $fotografia->autor?->nome ?: 'Não informado' }}</dd>
                        </div>

                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-[#55709B]">Pessoas retratadas/relacionadas</dt>
                            <dd class="mt-1 text-sm text-[#173F7A]">
                                @if ($fotografia->pessoas->isEmpty())
                                    Nenhuma pessoa vinculada.
                                @else
                                    {{ $fotografia->pessoas->pluck('nome')->join(', ') }}
                                @endif
                            </dd>
                        </div>
                    </dl>
                </section>
            </div>

            <aside class="space-y-6">
                <section class="rounded-lg border border-[#D8E2EF] bg-white p-6 shadow-sm shadow-[#173F7A]/5">
                    <h2 class="font-serif text-2xl font-semibold text-[#173F7A]">Auditoria</h2>

                    <dl class="mt-5 space-y-5">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-[#55709B]">Criado em</dt>
                            <dd class="mt-1 text-sm text-[#173F7A]">{{ $fotografia->created_at?->format('d/m/Y H:i') ?: 'Não registrado' }}</dd>
                        </div>

                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-[#55709B]">Criado por</dt>
                            <dd class="mt-1 text-sm text-[#173F7A]">{{ $fotografia->criadoPor?->nome ?: 'Não registrado' }}</dd>
                        </div>

                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-[#55709B]">Última alteração</dt>
                            <dd class="mt-1 text-sm text-[#173F7A]">{{ $fotografia->updated_at?->format('d/m/Y H:i') ?: 'Não registrada' }}</dd>
                        </div>

                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-[#55709B]">Alterado por</dt>
                            <dd class="mt-1 text-sm text-[#173F7A]">{{ $fotografia->atualizadoPor?->nome ?: 'Não registrado' }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="rounded-lg border border-[#D8E2EF] bg-white p-6 shadow-sm shadow-[#173F7A]/5">
                    <h2 class="font-serif text-2xl font-semibold text-[#173F7A]">Arquivos</h2>

                    @if ($fotografia->arquivos->isEmpty())
                        <p class="mt-3 text-sm leading-6 text-[#55709B]">Nenhum arquivo vinculado.</p>
                    @else
                        <ul class="mt-4 divide-y divide-[#D8E2EF]">
                            @foreach ($fotografia->arquivos as $arquivo)
                                <li class="py-3">
                                    <p class="text-sm font-semibold text-[#173F7A]">{{ $arquivo->nome_original }}</p>
                                    <p class="mt-1 text-xs text-[#55709B]">
                                        {{ $arquivo->versao_arquivo }} · {{ $arquivo->mime_type }} · {{ number_format($arquivo->file_size / 1024, 1, ',', '.') }} KB
                                    </p>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            </aside>
        </div>
    </div>
</x-layouts.admin>
