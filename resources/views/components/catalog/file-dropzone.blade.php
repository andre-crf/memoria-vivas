@props([
    'name',
    'mimeTypes',
    'extensions',
    'maxKb',
])

@php
    $formats = collect($extensions)
        ->map(fn (string $extension): string => $extension === 'webp' ? 'WebP' : mb_strtoupper($extension))
        ->join(', ', ' ou ');
    $maxLabel = number_format($maxKb / 1024, 0, ',', '.').' MB';
    $settings = [
        'extensions' => array_values($extensions),
        'maxBytes' => $maxKb * 1024,
        'formats' => $formats,
        'maxLabel' => $maxLabel,
    ];
@endphp

{{-- O input nativo fica escondido, mas é ele que recebe o foco (Enter/Espaço abrem o seletor) e vai no POST. --}}
<div x-data="fileDropzone(@js($settings))" {{ $attributes }}>
    <label
        for="{{ $name }}"
        x-show="file === null"
        :data-dragging="dragging"
        @dragenter.prevent="dragEnter()"
        @dragover.prevent
        @dragleave.prevent="dragLeave()"
        @drop.prevent="drop($event)"
        class="group flex cursor-pointer flex-col items-center gap-2 rounded-lg border border-dashed border-mist-400 bg-mist-50 px-6 py-8 text-center transition-colors hover:border-mist-600 hover:bg-white data-dragging:border-institutional data-dragging:bg-institutional-soft has-[input:focus-visible]:outline-2 has-[input:focus-visible]:outline-offset-2 has-[input:focus-visible]:outline-institutional"
    >
        <input
            id="{{ $name }}"
            name="{{ $name }}"
            type="file"
            accept="{{ implode(',', $mimeTypes) }}"
            class="sr-only"
            aria-describedby="{{ $name }}-formatos"
            @change="change($event)"
        >
        <span class="grid size-11 place-items-center rounded-full bg-white text-institutional ring-1 ring-mist-200 transition-colors group-data-dragging:bg-institutional group-data-dragging:text-white" aria-hidden="true">
            <x-lucide-upload class="size-5" />
        </span>
        <span class="text-[0.9rem] font-semibold text-ink-950" x-text="dragging ? 'Solte o arquivo para enviar' : 'Arraste e solte seu arquivo aqui'">Arraste e solte seu arquivo aqui</span>
        <span class="text-[0.8rem] text-ink-600">ou <span class="font-semibold text-institutional underline decoration-mist-500 underline-offset-4">clique para selecionar um arquivo</span></span>
        <span id="{{ $name }}-formatos" class="text-[0.72rem] text-ink-500">{{ $formats }} · até {{ $maxLabel }}</span>
    </label>

    <div class="overflow-hidden rounded-lg border border-mist-200" data-testid="file-preview" x-show="file !== null" style="display: none;">
        {{-- Prévia grande o bastante para reconhecer a fotografia antes de enviar. --}}
        <div class="grid min-h-40 place-items-center bg-mist-150 p-2">
            <template x-if="isImage && !previewFailed">
                <img class="block h-auto max-h-[28rem] w-auto max-w-full object-contain" :src="previewUrl" :alt="`Pré-visualização de ${file.name}`" x-on:error="previewFailed = true">
            </template>
            <template x-if="file !== null && (!isImage || previewFailed)">
                <span class="grid justify-items-center gap-2 text-[0.8rem] font-semibold text-ink-600">
                    <x-lucide-file-text class="size-10 text-institutional" aria-hidden="true" />
                    <span x-text="isPdf ? 'PDF' : 'Prévia indisponível'"></span>
                </span>
            </template>
        </div>
        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-mist-200 bg-white px-4 py-3">
            <div class="grid min-w-0 flex-1 gap-0.5 *:truncate">
                <strong class="text-[0.875rem] text-ink-950" x-text="file?.name"></strong>
                <span class="text-[0.75rem] text-ink-500" x-text="metadata"></span>
            </div>
            <div class="flex flex-none gap-2">
                <x-catalog.button variant="secondary" size="sm" icon="refresh-cw" x-on:click="replace()">Substituir</x-catalog.button>
                <x-catalog.button variant="secondary" size="sm" icon="trash-2" x-on:click="remove()">Remover</x-catalog.button>
            </div>
        </div>
    </div>

    <p class="mt-2 text-[0.75rem] leading-[1.45] text-danger" role="alert" x-text="error" x-show="error !== ''" x-cloak></p>
</div>
