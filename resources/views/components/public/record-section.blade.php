@props([
    'title',
])

{{-- Bloco da coluna documental da fotografia: rótulo discreto em caixa alta e conteúdo compacto. --}}
<section {{ $attributes }}>
    <h2 class="text-[0.72rem] font-bold tracking-[0.12em] text-ink-500 uppercase">{{ $title }}</h2>
    <div class="mt-3">
        {{ $slot }}
    </div>
</section>
