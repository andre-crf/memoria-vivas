{{--
    Torna a linha inteira da tabela clicável: é um link real, esticado sobre a <tr class="relative">,
    então teclado, leitor de tela e Ctrl+clique continuam funcionando. Os botões de ação da linha
    ficam acima dele com "relative z-10".
--}}
<a {{ $attributes->class('rounded-sm outline-none after:absolute after:inset-0 focus-visible:after:outline-2 focus-visible:after:-outline-offset-2 focus-visible:after:outline-institutional') }}>{{ $slot }}</a>
