<?php

namespace Tests\Feature;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

/**
 * Componentes do Design System (Etapa 3): variantes, estados e repasse de atributos.
 */
class ComponentesFundamentaisTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Componentes renderizados fora de uma requisição não recebem o $errors do middleware.
        view()->share('errors', new ViewErrorBag);
    }

    public function test_button_defaults_to_type_button_and_keeps_explicit_submit(): void
    {
        $this->assertSame('button', $this->first('<x-catalog.button>Salvar</x-catalog.button>', '//button')->getAttribute('type'));
        $this->assertSame('submit', $this->first('<x-catalog.button type="submit">Salvar</x-catalog.button>', '//button')->getAttribute('type'));
    }

    public function test_button_keeps_primary_and_secondary_presentation(): void
    {
        $primary = $this->classes('<x-catalog.button>Salvar</x-catalog.button>', '//button');
        $secondary = $this->classes('<x-catalog.button variant="secondary" size="sm">Salvar</x-catalog.button>', '//button');

        foreach (['bg-institutional', 'text-white', 'hover:bg-institutional-strong', 'min-h-10', 'px-4', 'text-ui'] as $classe) {
            $this->assertContains($classe, $primary);
        }
        foreach (['border-mist-300', 'bg-white', 'text-ink-800', 'min-h-8', 'px-3'] as $classe) {
            $this->assertContains($classe, $secondary);
        }
    }

    public function test_button_variants_have_distinct_styles_and_unknown_variant_falls_back_to_primary(): void
    {
        $esperado = [
            'outline' => 'border-institutional',
            'danger' => 'text-danger',
            'ghost' => 'hover:bg-mist-150',
        ];

        foreach ($esperado as $variante => $classe) {
            $this->assertContains($classe, $this->classes("<x-catalog.button variant=\"{$variante}\">Ação</x-catalog.button>", '//button'), $variante);
        }

        // Uma única cor de contorno por botão: com duas, a que vale dependeria da ordem das regras no CSS.
        $perigo = $this->classes('<x-catalog.button variant="danger">Excluir</x-catalog.button>', '//button');
        $this->assertContains('focus-visible:outline-danger', $perigo);
        $this->assertNotContains('focus-visible:outline-institutional', $perigo);
        $this->assertContains('focus-visible:outline-institutional', $this->classes('<x-catalog.button variant="ghost">Ação</x-catalog.button>', '//button'));
        $this->assertContains('bg-institutional', $this->classes('<x-catalog.button variant="inexistente">Ação</x-catalog.button>', '//button'));
    }

    public function test_button_forwards_html_alpine_livewire_and_data_attributes(): void
    {
        $botao = $this->first(
            '<x-catalog.button id="salvar" class="w-full" wire:click="save" wire:loading.attr="disabled" x-on:click="aberto = false" data-teste="ok" aria-label="Salvar registro">Salvar</x-catalog.button>',
            '//button',
        );

        $this->assertSame('salvar', $botao->getAttribute('id'));
        $this->assertSame('save', $botao->getAttribute('wire:click'));
        $this->assertSame('disabled', $botao->getAttribute('wire:loading.attr'));
        $this->assertSame('aberto = false', $botao->getAttribute('x-on:click'));
        $this->assertSame('ok', $botao->getAttribute('data-teste'));
        $this->assertSame('Salvar registro', $botao->getAttribute('aria-label'));
        $this->assertContains('w-full', preg_split('/\s+/', trim($botao->getAttribute('class'))));
    }

    public function test_button_renders_icon_and_link(): void
    {
        $html = $this->render('<x-catalog.button href="/admin" icon="plus">Novo</x-catalog.button>');
        $link = $this->first($html, '//a');

        $this->assertSame('/admin', $link->getAttribute('href'));
        $this->assertSame(0, $this->xpath($html)->query('//button')->length);
        $this->assertSame(1, $this->xpath($html)->query('//a/svg[@aria-hidden="true"]')->length);
    }

    public function test_disabled_button_uses_native_attribute(): void
    {
        $botao = $this->first('<x-catalog.button disabled>Salvar</x-catalog.button>', '//button');

        $this->assertTrue($botao->hasAttribute('disabled'));
        $this->assertFalse($botao->hasAttribute('aria-busy'));
    }

    public function test_disabled_link_cannot_be_followed_or_focused(): void
    {
        $link = $this->first('<x-catalog.button href="/admin/fotografias" disabled>Ver</x-catalog.button>', '//a');

        $this->assertFalse($link->hasAttribute('href'));
        $this->assertFalse($link->hasAttribute('disabled'));
        $this->assertFalse($link->hasAttribute('tabindex'));
        $this->assertSame('link', $link->getAttribute('role'));
        $this->assertSame('true', $link->getAttribute('aria-disabled'));
    }

    public function test_loading_button_is_disabled_busy_and_announced(): void
    {
        $html = $this->render('<x-catalog.button type="submit" icon="save" loading>Salvar</x-catalog.button>');
        $botao = $this->first($html, '//button');

        $this->assertTrue($botao->hasAttribute('disabled'));
        $this->assertSame('true', $botao->getAttribute('aria-busy'));
        $this->assertSame('submit', $botao->getAttribute('type'));
        // Spinner decorativo e texto para leitores de tela, exibidos pelo aria-busy.
        $this->assertSame(1, $this->xpath($html)->query('//button/svg[contains(@class, "animate-spin")][@aria-hidden="true"]')->length);
        $this->assertStringContainsString('(processando)', $botao->textContent);
        // O botão contém o sr-only (absoluto); sem isso ele aumenta a largura de rolagem de tabelas e da página.
        $this->assertContains('relative', preg_split('/\s+/', trim($botao->getAttribute('class'))));
    }

    public function test_loading_link_loses_href(): void
    {
        $link = $this->first('<x-catalog.button href="/exportar" loading>Exportar</x-catalog.button>', '//a');

        $this->assertFalse($link->hasAttribute('href'));
        $this->assertSame('true', $link->getAttribute('aria-disabled'));
        $this->assertSame('true', $link->getAttribute('aria-busy'));
    }

    public function test_button_without_loading_has_no_spinner_markup(): void
    {
        $html = $this->render('<x-catalog.button icon="plus">Novo</x-catalog.button>');

        $this->assertStringNotContainsString('animate-spin', $html);
        $this->assertStringNotContainsString('(processando)', $html);
    }

    public function test_dynamic_busy_binding_renders_spinner_without_disabling(): void
    {
        $html = $this->render('<x-catalog.button x-bind:aria-busy="enviando">Enviar</x-catalog.button>');
        $botao = $this->first($html, '//button');

        $this->assertSame('enviando', $botao->getAttribute('x-bind:aria-busy'));
        $this->assertFalse($botao->hasAttribute('disabled'));
        $this->assertStringContainsString('animate-spin', $html);
    }

    public function test_control_styles_invalid_and_disabled_states_for_every_element(): void
    {
        foreach (['input' => '<x-catalog.control id="c" aria-invalid="true" disabled />', 'select' => '<x-catalog.control as="select" id="c" aria-invalid="true" disabled><option>A</option></x-catalog.control>', 'textarea' => '<x-catalog.control as="textarea" id="c" aria-invalid="true" disabled>Texto</x-catalog.control>'] as $tag => $blade) {
            $campo = $this->first($blade, "//{$tag}");
            $classes = preg_split('/\s+/', trim($campo->getAttribute('class')));

            $this->assertSame('true', $campo->getAttribute('aria-invalid'), $tag);
            $this->assertTrue($campo->hasAttribute('disabled'), $tag);
            foreach (['aria-invalid:border-danger', 'aria-invalid:focus:border-danger', 'disabled:bg-mist-100', 'disabled:cursor-not-allowed', 'focus:shadow-focus'] as $classe) {
                $this->assertContains($classe, $classes, "{$tag}: {$classe}");
            }
        }
    }

    public function test_control_forwards_livewire_and_alpine_attributes(): void
    {
        $campo = $this->first('<x-catalog.control id="data" type="date" wire:model.live.debounce.300ms="data" x-on:input="update($event.target.value)" ::disabled="!visivel" />', '//input');

        $this->assertSame('data', $campo->getAttribute('wire:model.live.debounce.300ms'));
        $this->assertSame('update($event.target.value)', $campo->getAttribute('x-on:input'));
        $this->assertSame('!visivel', $campo->getAttribute(':disabled'));
        $this->assertSame('date', $campo->getAttribute('type'));
    }

    public function test_control_error_prop_links_validation_message_and_keeps_hints(): void
    {
        $this->compartilharErros(['titulo' => ['O título é obrigatório.']], ['password' => ['A senha atual está incorreta.']]);

        // Sem erro na chave: nada muda.
        $semErro = $this->first('<x-catalog.control id="descricao" name="descricao" error="descricao" />', '//input');
        $this->assertFalse($semErro->hasAttribute('aria-invalid'));
        $this->assertFalse($semErro->hasAttribute('aria-describedby'));

        $comErro = $this->first('<x-catalog.control id="titulo" name="titulo" error="titulo" />', '//input');
        $this->assertSame('true', $comErro->getAttribute('aria-invalid'));
        $this->assertSame('titulo-erro', $comErro->getAttribute('aria-describedby'));

        // Bag nomeado e dica já descrita: a mensagem é acrescentada depois da dica.
        $senha = $this->first('<x-catalog.control id="password" type="password" aria-describedby="password-dica" error="password" error-bag="passwordUpdate" />', '//input');
        $this->assertSame('true', $senha->getAttribute('aria-invalid'));
        $this->assertSame('password-dica password-erro', $senha->getAttribute('aria-describedby'));

        // A mesma chave em outro bag não marca o campo.
        $this->assertFalse($this->first('<x-catalog.control id="password" error="password" />', '//input')->hasAttribute('aria-invalid'));

        // Select e textarea seguem a mesma regra; sem id, não há o que apontar.
        $this->assertSame('titulo-erro', $this->first('<x-catalog.control as="select" id="titulo" error="titulo"><option>A</option></x-catalog.control>', '//select')->getAttribute('aria-describedby'));
        $semId = $this->first('<x-catalog.control as="textarea" error="titulo">Texto</x-catalog.control>', '//textarea');
        $this->assertSame('true', $semId->getAttribute('aria-invalid'));
        $this->assertFalse($semId->hasAttribute('aria-describedby'));
    }

    public function test_error_field_prop_renders_message_with_predictable_id(): void
    {
        $this->compartilharErros(['titulo' => ['O título é obrigatório.', 'Segunda mensagem.'], 'q' => ['Busca longa demais.']], ['password' => ['A senha atual está incorreta.']]);

        $mensagem = $this->first('<x-catalog.error field="titulo" as="p" />', '//p');
        $this->assertSame('titulo-erro', $mensagem->getAttribute('id'));
        $this->assertSame('O título é obrigatório.', trim($mensagem->textContent));
        $this->assertContains('text-danger', preg_split('/\s+/', $mensagem->getAttribute('class')));

        $this->assertSame('download-search-erro', $this->first('<x-catalog.error field="q" for="download-search" as="li" />', '//li')->getAttribute('id'));
        $this->assertSame('password-erro', $this->first('<x-catalog.error field="password" bag="passwordUpdate" />', '//small')->getAttribute('id'));

        // Sem erro na chave, nada é renderizado.
        $this->assertSame('', trim($this->render('<x-catalog.error field="descricao" as="p" />')));

        // Uso antigo, com o texto no slot, continua igual.
        $antigo = $this->first('<x-catalog.error role="alert">Mensagem livre</x-catalog.error>', '//small');
        $this->assertSame('alert', $antigo->getAttribute('role'));
        $this->assertFalse($antigo->hasAttribute('id'));
    }

    public function test_section_without_title_is_only_the_card(): void
    {
        $html = $this->render('<x-catalog.section class="mt-4"><p>Conteúdo</p></x-catalog.section>');
        $xpath = $this->xpath($html);

        $this->assertSame(0, $xpath->query('//header')->length);
        $this->assertSame(0, $xpath->query('//h2')->length);
        $this->assertContains('shadow-card', preg_split('/\s+/', $this->first($html, '//section')->getAttribute('class')));

        $comTitulo = $this->xpath($this->render('<x-catalog.section title="Dados" description="Descrição"><p>Conteúdo</p></x-catalog.section>'));
        $this->assertSame('Dados', trim($comTitulo->query('//header/div/h2')->item(0)->textContent));
    }

    public function test_label_weight_applies_only_to_label_text(): void
    {
        $html = $this->render('<x-catalog.label for="t" text="Título" required><x-catalog.control id="t" /><span class="dica">Dica</span></x-catalog.label>');
        $label = $this->first($html, '//label');

        $this->assertNotContains('font-[650]', preg_split('/\s+/', trim($label->getAttribute('class'))));
        $this->assertSame(1, $this->xpath($html)->query('//label/span[contains(@class, "font-[650]")][contains(., "Título")]')->length);
        $this->assertStringContainsString('obrigatório', $label->textContent);
    }

    public function test_page_header_renders_h1_and_optional_parts(): void
    {
        $html = $this->render(<<<'BLADE'
            <x-admin.page-header eyebrow="Acervo" title="Fotografias" description="Lista de fotografias." back-href="/admin" back-label="Voltar ao painel" class="mb-6">
                <span id="badges">Publicado</span>
                <x-slot:actions><x-catalog.button href="/admin/fotografias/create">Nova</x-catalog.button></x-slot:actions>
            </x-admin.page-header>
            BLADE);
        $xpath = $this->xpath($html);

        $this->assertSame('Fotografias', trim($xpath->query('//header/div/h1')->item(0)->textContent));
        $this->assertSame('/admin', $xpath->query('//header//a[contains(., "Voltar ao painel")]')->item(0)->getAttribute('href'));
        $this->assertSame(1, $xpath->query('//header//p[.="Acervo"]')->length);
        $this->assertSame(1, $xpath->query('//header//p[.="Lista de fotografias."]')->length);
        $this->assertSame(1, $xpath->query('//header//*[@id="badges"]')->length);
        $this->assertSame(1, $xpath->query('//header/div[2]/a[@href="/admin/fotografias/create"]')->length);
        $this->assertContains('mb-6', preg_split('/\s+/', $xpath->query('//header')->item(0)->getAttribute('class')));
    }

    public function test_page_header_with_title_only_has_no_optional_markup(): void
    {
        $html = $this->render('<x-admin.page-header title="Meu perfil" />');
        $xpath = $this->xpath($html);

        $this->assertSame(1, $xpath->query('//h1')->length);
        $this->assertSame(0, $xpath->query('//header//a')->length);
        $this->assertSame(0, $xpath->query('//header//p')->length);
        $this->assertSame(1, $xpath->query('//header/div')->length);
    }

    public function test_badge_tones_and_attributes(): void
    {
        $esperado = [
            'neutral' => 'bg-mist-150',
            'heritage' => 'bg-heritage-100',
            'info' => 'bg-institutional-soft',
            'success' => 'bg-success-soft',
            'warning' => 'bg-warning-soft',
            'danger' => 'bg-error-soft',
        ];

        foreach ($esperado as $tom => $classe) {
            $this->assertContains($classe, $this->classes("<x-ui.badge tone=\"{$tom}\">Estado</x-ui.badge>", '/html/body/span'), $tom);
        }

        $this->assertContains('bg-mist-150', $this->classes('<x-ui.badge>Rascunho</x-ui.badge>', '/html/body/span'));
        $this->assertContains('bg-mist-150', $this->classes('<x-ui.badge tone="publicado">Publicado</x-ui.badge>', '/html/body/span'));

        $html = $this->render('<x-ui.badge tone="danger" icon="circle-x" data-status="falhou" title="Geração falhou">Falhou</x-ui.badge>');
        $badge = $this->first($html, '/html/body/span');
        $this->assertSame('falhou', $badge->getAttribute('data-status'));
        $this->assertSame('Geração falhou', $badge->getAttribute('title'));
        $this->assertSame('Falhou', trim($badge->textContent));
        $this->assertSame(1, $this->xpath($html)->query('/html/body/span/svg[@aria-hidden="true"]')->length);
    }

    public function test_alert_roles_follow_tone_and_can_be_overridden(): void
    {
        $this->assertFalse($this->first('<x-ui.alert>Informação</x-ui.alert>', '/html/body/div')->hasAttribute('role'));
        $this->assertFalse($this->first('<x-ui.alert tone="warning">Atenção</x-ui.alert>', '/html/body/div')->hasAttribute('role'));
        $this->assertSame('status', $this->first('<x-ui.alert tone="success">Salvo</x-ui.alert>', '/html/body/div')->getAttribute('role'));
        $this->assertSame('alert', $this->first('<x-ui.alert tone="danger">Erro</x-ui.alert>', '/html/body/div')->getAttribute('role'));
        $this->assertSame('note', $this->first('<x-ui.alert tone="danger" role="note">Erro</x-ui.alert>', '/html/body/div')->getAttribute('role'));
        $this->assertFalse($this->first('<x-ui.alert tone="danger" :role="false">Erro</x-ui.alert>', '/html/body/div')->hasAttribute('role'));
    }

    public function test_alert_title_prefix_icon_and_attributes(): void
    {
        $comTitulo = $this->render('<x-ui.alert tone="danger" title="Revise os campos" id="resumo" class="mb-6"><ul><li>Título obrigatório.</li></ul></x-ui.alert>');
        $alerta = $this->first($comTitulo, '/html/body/div');

        $this->assertSame('resumo', $alerta->getAttribute('id'));
        $this->assertContains('mb-6', preg_split('/\s+/', trim($alerta->getAttribute('class'))));
        $this->assertContains('bg-error-soft', preg_split('/\s+/', trim($alerta->getAttribute('class'))));
        $this->assertSame('Revise os campos', trim($this->xpath($comTitulo)->query('//p[contains(@class, "font-semibold")]')->item(0)->textContent));
        $this->assertSame(1, $this->xpath($comTitulo)->query('//ul/li')->length);
        $this->assertSame(0, $this->xpath($comTitulo)->query('//*[contains(@class, "sr-only")]')->length);
        $this->assertSame(1, $this->xpath($comTitulo)->query('/html/body/div/svg[@aria-hidden="true"]')->length);

        $semTitulo = $this->render('<x-ui.alert tone="warning">Versão ausente.</x-ui.alert>');
        $this->assertSame('Atenção:', trim($this->xpath($semTitulo)->query('//*[contains(@class, "sr-only")]')->item(0)->textContent));
    }

    public function test_empty_state_title_level_description_icon_and_action(): void
    {
        $html = $this->render(<<<'BLADE'
            <x-ui.empty-state heading="h3" icon="inbox" title="Nenhuma categoria cadastrada" description="Cadastre a primeira categoria." data-testid="vazio">
                <x-slot:action><x-catalog.button href="/admin/categorias/create">Nova categoria</x-catalog.button></x-slot:action>
            </x-ui.empty-state>
            BLADE);
        $xpath = $this->xpath($html);

        $this->assertSame('vazio', $xpath->query('/html/body/div')->item(0)->getAttribute('data-testid'));
        $this->assertSame('Nenhuma categoria cadastrada', trim($xpath->query('//h3')->item(0)->textContent));
        $this->assertSame(1, $xpath->query('//p[.="Cadastre a primeira categoria."]')->length);
        $this->assertSame(1, $xpath->query('//span[@aria-hidden="true"]/svg')->length);
        $this->assertSame(1, $xpath->query('//a[@href="/admin/categorias/create"]')->length);
    }

    public function test_empty_state_minimal_and_invalid_heading_level(): void
    {
        $html = $this->render('<x-ui.empty-state heading="div" title="Nada por aqui" />');
        $xpath = $this->xpath($html);

        $this->assertSame(1, $xpath->query('//h2[.="Nada por aqui"]')->length);
        $this->assertSame(0, $xpath->query('//p')->length);
        $this->assertSame(0, $xpath->query('//svg')->length);
        $this->assertSame(0, $xpath->query('//a')->length);
    }

    /**
     * @param  array<string, list<string>>  $padrao
     * @param  array<string, list<string>>  $senha
     */
    private function compartilharErros(array $padrao, array $senha = []): void
    {
        $erros = new ViewErrorBag;
        $erros->put('default', new MessageBag($padrao));
        $erros->put('passwordUpdate', new MessageBag($senha));
        view()->share('errors', $erros);
    }

    private function render(string $blade): string
    {
        return (string) $this->blade($blade);
    }

    private function xpath(string $html): DOMXPath
    {
        $dom = new DOMDocument;
        // Atributos como wire:click e x-on:click não são HTML válido para o parser, mas continuam acessíveis.
        @$dom->loadHTML('<?xml encoding="utf-8"?><html><body>'.$html.'</body></html>', LIBXML_NOERROR);

        return new DOMXPath($dom);
    }

    private function first(string $bladeOrHtml, string $query): DOMElement
    {
        $html = str_contains($bladeOrHtml, '<x-') ? $this->render($bladeOrHtml) : $bladeOrHtml;
        $node = $this->xpath($html)->query($query)->item(0);
        $this->assertInstanceOf(DOMElement::class, $node, "Elemento não encontrado: {$query}");

        return $node;
    }

    /** @return list<string> */
    private function classes(string $blade, string $query): array
    {
        return preg_split('/\s+/', trim($this->first($blade, $query)->getAttribute('class')));
    }
}
