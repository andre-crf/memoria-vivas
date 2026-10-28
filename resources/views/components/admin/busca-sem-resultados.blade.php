@props([
    'action',
    'mensagem' => 'Nenhum registro encontrado para a pesquisa',
    'dica' => 'Revise o texto pesquisado ou tente outro termo.',
    'limpar' => 'Limpar pesquisa',
])

<div {{ $attributes->class('px-6 py-16 text-center') }}>
    <h2 class="text-xl font-semibold text-stone-950">{{ $mensagem }}</h2>
    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-stone-600">{{ $dica }}</p>
    <a href="{{ $action }}" class="mt-4 inline-flex text-sm font-semibold text-institutional hover:text-institutional-strong hover:underline hover:underline-offset-4">{{ $limpar }}</a>
</div>
