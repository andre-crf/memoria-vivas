{{--
    Painel de distribuição do relatório de downloads: grupos { rotulo, total } de EstatisticasDownloads,
    com percentual sobre $painel['base'] (os downloads considerados pelo painel). $painel['nivel']: h2 (padrão) ou h3.
--}}
@php $nivel = $painel['nivel'] ?? 'h2'; @endphp
<section class="rounded-lg border border-line bg-white p-5 shadow-card sm:p-6" aria-labelledby="{{ $painel['id'] }}">
    <div class="border-b border-line pb-4">
        <p class="text-xs font-bold tracking-wide text-institutional uppercase">{{ $painel['eyebrow'] }}</p>
        <{{ $nivel }} id="{{ $painel['id'] }}" class="mt-1 text-[1.4rem] font-semibold text-institutional">{{ $painel['titulo'] }}</{{ $nivel }}>
    </div>
    @if ($painel['grupos']->isEmpty() || $painel['base'] === 0)
        <p class="py-10 text-sm text-fg-muted">{{ $painel['vazio'] }}</p>
    @else
        <ol class="mt-5 space-y-5">
            @foreach ($painel['grupos'] as $grupo)
                @php $percentual = $grupo->total / $painel['base'] * 100; @endphp
                <li>
                    <div class="flex items-start justify-between gap-3 text-sm">
                        <span class="min-w-0 font-medium text-ink-800">{{ $grupo->rotulo }}</span>
                        <span class="flex-none whitespace-nowrap font-bold tabular-nums text-institutional">{{ number_format($percentual, 1, ',', '.') }}% <span class="font-normal text-fg-subtle">({{ $grupo->total }})</span></span>
                    </div>
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-mist-150" aria-hidden="true"><div class="h-full rounded-full bg-institutional-light" style="width: {{ $percentual }}%"></div></div>
                </li>
            @endforeach
        </ol>
    @endif
</section>
