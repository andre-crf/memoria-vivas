<x-layouts.admin title="Contribuição | Memórias Vivas">
    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
        <x-admin.page-header
            class="mb-6"
            eyebrow="Contribuição recebida"
            :title="$contribuicao->titulo"
            :back-href="route('admin.contribuicoes.index')"
            back-label="Voltar às contribuições"
        >
            <p class="text-sm text-fg-muted">Recebida em <x-data-hora :valor="$contribuicao->created_at" formato="curto" /></p>
        </x-admin.page-header>

        <div class="grid gap-6">
            <x-catalog.section title="Dados do envio">
                <dl class="grid gap-3 sm:grid-cols-2">
                    <x-admin.info-block label="Colaborador">{{ $contribuicao->nome }}</x-admin.info-block>
                    <x-admin.info-block label="E-mail">
                        <a class="break-all font-semibold text-institutional underline underline-offset-4 hover:text-institutional-strong focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-institutional" href="mailto:{{ $contribuicao->email }}">{{ $contribuicao->email }}</a>
                    </x-admin.info-block>
                    <x-admin.info-block label="Local" :empty="! $contribuicao->local">{{ $contribuicao->local ?: 'Não informado' }}</x-admin.info-block>
                    <x-admin.info-block label="Época" :empty="! $contribuicao->periodo">{{ $contribuicao->periodo ?: 'Não informada' }}</x-admin.info-block>
                </dl>
            </x-catalog.section>

            <x-catalog.section title="História e contexto">
                <p @class(['whitespace-pre-line text-sm leading-6', 'text-ink-700' => $contribuicao->historia, 'text-fg-subtle' => ! $contribuicao->historia])>{{ $contribuicao->historia ?: 'Não informado.' }}</p>
            </x-catalog.section>

            <x-catalog.section flush title="Fotografias enviadas" description="O envio não publica automaticamente esses arquivos. Cadastre manualmente no acervo após a análise.">
                <ul class="divide-y divide-line">
                    @foreach ($contribuicao->arquivos as $arquivo)
                        <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 text-sm sm:px-6">
                            <span class="min-w-0 break-all text-ink-800">{{ $arquivo->nome_original }} <span class="text-fg-subtle">({{ number_format($arquivo->tamanho / 1048576, 1, ',', '.') }} MB)</span></span>
                            <x-catalog.button variant="secondary" size="sm" icon="download" :href="route('admin.contribuicoes.arquivos.download', [$contribuicao, $arquivo])">Baixar imagem<span class="sr-only"> {{ $arquivo->nome_original }}</span></x-catalog.button>
                        </li>
                    @endforeach
                </ul>
            </x-catalog.section>
        </div>
    </div>
</x-layouts.admin>
