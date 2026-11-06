@props([
    'as' => 'input',
    'size' => 'md',
    // Chave do campo nos erros de validação: com erro, o controle recebe aria-invalid e aponta a mensagem de x-catalog.error field.
    'error' => null,
    'errorBag' => 'default',
])

@php
    if ($error !== null && isset($errors) && $errors->getBag($errorBag)->has($error)) {
        $id = $attributes->get('id');
        // Mesmo id que x-catalog.error gera: "{id do controle}-erro". Descrições já passadas (dicas) são mantidas.
        $describedBy = trim($attributes->get('aria-describedby', '').($id ? ' '.$id.'-erro' : ''));
        $attributes = $attributes->except('aria-describedby')->merge(array_filter([
            'aria-invalid' => 'true',
            'aria-describedby' => $describedBy,
        ]));
    }

    $classes = 'w-full rounded-md border border-mist-300 bg-white px-3 font-normal text-ink-950 outline-none transition-[border-color,box-shadow] duration-150 ease-[ease] focus:border-institutional focus:shadow-focus ';
    // Inválido: o mesmo aria-invalid que os leitores de tela anunciam controla a aparência.
    $classes .= 'aria-invalid:border-danger aria-invalid:focus:border-danger aria-invalid:focus:shadow-[0_0_0_3px_rgb(180_35_24/0.14)] ';
    $classes .= 'disabled:cursor-not-allowed disabled:border-mist-200 disabled:bg-mist-100 disabled:text-ink-500 ';
    $classes .= match (true) {
        $size === 'sm' => 'min-h-[2.35rem] py-[0.45rem] text-[0.78rem]',
        $as === 'textarea' => 'min-h-[5.5rem] resize-y py-[0.62rem] text-[0.875rem]',
        default => 'min-h-[2.65rem] py-[0.62rem] text-[0.875rem]',
    };
@endphp

@if ($as === 'input')
    <input {{ $attributes->class($classes) }}>
@else
    <{{ $as }} {{ $attributes->class($classes) }}>{{ $slot }}</{{ $as }}>
@endif
