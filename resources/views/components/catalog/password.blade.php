@props([
    'id',
    'name' => null,
    'label',
    'required' => false,
    // Com required, mostra "obrigatório" no rótulo. O login usa false: lá nenhum campo leva o marcador.
    'marcador' => true,
    'autocomplete' => 'current-password',
    // Checklist ao vivo dos requisitos de senha forte (Password::defaults()). Use só em senhas novas.
    'requisitos' => false,
    // Mesmos parâmetros de x-catalog.control / x-catalog.error.
    'error' => null,
    'errorBag' => 'default',
])

@php
    $campo = $name ?? $id;
    // Chave de erro: por padrão, o próprio nome do campo.
    $chaveErro = $error ?? $campo;
    $requisitosId = $id.'-requisitos';
    $describedBy = trim(($attributes->get('aria-describedby') ?? '').($requisitos ? ' '.$requisitosId : ''));
    $itens = [
        'tamanho' => 'Pelo menos 8 caracteres',
        'caixa' => 'Letras maiúsculas e minúsculas',
        'numero' => 'Pelo menos um número',
        'simbolo' => 'Pelo menos um símbolo (ex.: ! @ # -)',
    ];
@endphp

{{--
    Campo de senha completo: rótulo, controle, botão de mostrar/ocultar, requisitos opcionais e erro.
    O botão fica fora do <label> para não entrar no nome acessível do campo.
--}}
<div {{ $attributes->only('class')->class('grid gap-1.5') }} x-data="passwordField" data-password-field>
    <div class="relative">
        <x-catalog.label :for="$id" :text="$label" :required="$required && $marcador">
            <x-catalog.control
                :id="$id"
                :name="$campo"
                type="password"
                x-bind:type="visivel ? 'text' : 'password'"
                x-on:input="valor = $event.target.value"
                :autocomplete="$autocomplete"
                :required="$required"
                :aria-describedby="$describedBy !== '' ? $describedBy : null"
                :error="$chaveErro"
                :error-bag="$errorBag"
                class="pr-11"
                {{ $attributes->except(['class', 'aria-describedby']) }}
            />
        </x-catalog.label>

        <button
            type="button"
            class="absolute right-0 bottom-0 inline-flex h-[2.65rem] w-11 cursor-pointer items-center justify-center rounded-r-md text-ink-500 transition-colors hover:text-institutional focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-institutional"
            aria-label="Mostrar senha"
            aria-pressed="false"
            aria-controls="{{ $id }}"
            x-bind:aria-label="visivel ? 'Ocultar senha' : 'Mostrar senha'"
            x-bind:aria-pressed="visivel.toString()"
            x-on:click="alternar()"
            data-password-toggle
        >
            <x-lucide-eye class="size-[1.1rem]" aria-hidden="true" x-show="!visivel" />
            <x-lucide-eye-off class="size-[1.1rem]" aria-hidden="true" x-show="visivel" x-cloak />
        </button>
    </div>

    @if ($requisitos)
        <div id="{{ $requisitosId }}" class="text-caption text-fg-muted" data-password-requirements>
            <p>A senha deve ter:</p>
            <ul class="mt-1 grid gap-0.5 sm:grid-cols-2">
                @foreach ($itens as $chave => $texto)
                    <li
                        class="flex items-center gap-1.5 data-[ok=true]:text-success"
                        data-requisito="{{ $chave }}"
                        data-ok="false"
                        x-bind:data-ok="requisitos.{{ $chave }}.toString()"
                    >
                        <x-lucide-circle class="size-3.5 flex-none" aria-hidden="true" x-show="!requisitos.{{ $chave }}" />
                        <x-lucide-circle-check class="size-3.5 flex-none" aria-hidden="true" x-show="requisitos.{{ $chave }}" x-cloak />
                        <span>{{ $texto }}</span>
                        <span class="sr-only" x-text="requisitos.{{ $chave }} ? '(atendido)' : '(pendente)'"></span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <x-catalog.error :field="$chaveErro" :bag="$errorBag" :for="$id" as="p" />
</div>
