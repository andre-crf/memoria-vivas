<x-layouts.admin title="Contribuição | Memórias Vivas">
    <div class="mx-auto max-w-4xl px-6 py-8">
        <a href="{{ route('admin.contribuicoes.index') }}" class="text-sm font-semibold text-[#173E63] underline">&larr; Voltar às contribuições</a>
        <p class="mt-8 text-sm font-semibold text-[#173E63]">Recebida em <x-data-hora :valor="$contribuicao->created_at" formato="curto" /></p>
        <h1 class="mt-2 text-3xl font-semibold text-stone-950">{{ $contribuicao->titulo }}</h1>
        <dl class="mt-8 grid gap-5 border-y border-stone-200 py-6 text-sm sm:grid-cols-2">
            <div><dt class="font-semibold text-stone-600">Colaborador</dt><dd class="mt-1 text-stone-900">{{ $contribuicao->nome }}</dd></div>
            <div><dt class="font-semibold text-stone-600">E-mail</dt><dd class="mt-1"><a class="break-all text-[#173E63] underline" href="mailto:{{ $contribuicao->email }}">{{ $contribuicao->email }}</a></dd></div>
            <div><dt class="font-semibold text-stone-600">Local</dt><dd class="mt-1 text-stone-900">{{ $contribuicao->local ?: 'Não informado' }}</dd></div>
            <div><dt class="font-semibold text-stone-600">Época</dt><dd class="mt-1 text-stone-900">{{ $contribuicao->periodo ?: 'Não informada' }}</dd></div>
        </dl>
        <h2 class="mt-8 text-xl font-semibold">História e contexto</h2>
        <p class="mt-3 whitespace-pre-line text-stone-700">{{ $contribuicao->historia ?: 'Não informado.' }}</p>
        <h2 class="mt-10 text-xl font-semibold">Fotografias enviadas</h2>
        <ul class="mt-4 divide-y divide-stone-200 border-y border-stone-200">
            @foreach ($contribuicao->arquivos as $arquivo)
                <li class="flex flex-wrap items-center justify-between gap-3 py-4 text-sm">
                    <span class="min-w-0 break-all text-stone-800">{{ $arquivo->nome_original }} <span class="text-stone-500">({{ number_format($arquivo->tamanho / 1048576, 1, ',', '.') }} MB)</span></span>
                    <a class="font-semibold text-[#173E63] underline" href="{{ route('admin.contribuicoes.arquivos.download', [$contribuicao, $arquivo]) }}">Baixar imagem</a>
                </li>
            @endforeach
        </ul>
        <p class="mt-6 text-sm text-stone-600">O envio não publica automaticamente esses arquivos. Cadastre manualmente no acervo após a análise.</p>
    </div>
</x-layouts.admin>
