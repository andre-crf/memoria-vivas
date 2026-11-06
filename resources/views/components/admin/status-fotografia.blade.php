@props([
    'fotografia',
])

@php
    // Só apresentação: os status continuam os de ItemAcervo::STATUS. Rascunho e arquivado são neutros.
    $tons = ['em_revisao' => 'info', 'publicado' => 'success'];
@endphp

{{-- Status de catalogação de uma fotografia: listagem, lixeira, detalhes e painel. --}}
<x-ui.badge {{ $attributes }} :tone="$tons[$fotografia->status] ?? 'neutral'">{{ $fotografia->statusLabel() }}</x-ui.badge>
