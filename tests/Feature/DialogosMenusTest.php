<?php

namespace Tests\Feature;

use App\Enums\Visibilidade;
use App\Livewire\Admin\CatalogPicker;
use App\Models\ItemAcervo;
use App\Models\User;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Diálogos e menus do admin (Etapa 6B): apresentação comum (.admin-dialog, .admin-menu, x-catalog.button)
 * com os contratos de Alpine, Livewire e formulários de antes. A interação real está em
 * tests/Browser/dialogos-menus.spec.js e hybrid-flows.spec.js.
 */
class DialogosMenusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_danger_solid_variant_is_filled_red_with_red_focus(): void
    {
        view()->share('errors', new ViewErrorBag);
        $botao = $this->xpath((string) $this->blade('<x-catalog.button variant="danger-solid">Excluir</x-catalog.button>'))->query('//button')->item(0);

        foreach (['bg-danger', 'text-white', 'hover:bg-error-strong', 'focus-visible:outline-danger'] as $classe) {
            $this->assertContains($classe, $this->classes($botao), $classe);
        }
        $this->assertNotContains('focus-visible:outline-institutional', $this->classes($botao));
        $this->assertSame('button', $botao->getAttribute('type'));
    }

    public function test_confirmation_dialog_keeps_alpine_contract_and_distinct_destructive_action(): void
    {
        $resposta = $this->actingAs($this->admin())->get(route('admin.categorias.index'))->assertOk();
        $xpath = $this->dom($resposta);
        $raiz = $xpath->query('//*[@x-data="confirmationDialog"]')->item(0);
        $this->assertAtributosAlpine($resposta->getContent(), '<div x-data="confirmationDialog"', ['@submit.window' => 'intercept($event)']);

        $dialogo = $xpath->query('./dialog', $raiz)->item(0);
        foreach (['x-ref' => 'dialog', 'role' => 'alertdialog', 'aria-modal' => 'true', 'aria-labelledby' => 'confirmation-dialog-title', 'aria-describedby' => 'confirmation-dialog-message'] as $atributo => $valor) {
            $this->assertSame($valor, $dialogo->getAttribute($atributo), $atributo);
        }
        $this->assertAtributosAlpine($resposta->getContent(), 'role="alertdialog"', ['@cancel.prevent' => 'cancel()', '@close' => 'restoreFocus()', '@click.self' => 'cancel()'], '<dialog');
        $this->assertContains('admin-dialog', $this->classes($dialogo));
        $this->assertSame('title', $xpath->query('.//h2[@id="confirmation-dialog-title"]', $dialogo)->item(0)->getAttribute('x-text'));
        $this->assertSame('message', $xpath->query('.//p[@id="confirmation-dialog-message"]', $dialogo)->item(0)->getAttribute('x-text'));

        // Cancelar recebe o foco inicial (x-ref="cancel") e não envia nada.
        $cancelar = $xpath->query('.//button[@x-ref="cancel"]', $dialogo)->item(0);
        $this->assertSame('button', $cancelar->getAttribute('type'));
        $this->assertSame('cancel()', $cancelar->getAttribute('x-on:click'));

        // Duas ações de confirmação com o mesmo approve(); só uma aparece, conforme data-confirm-variant.
        $confirmacoes = $xpath->query('.//button[@*[name()="x-on:click"]="approve()"]', $dialogo);
        $this->assertSame(2, $confirmacoes->length);
        $porVariante = [];
        foreach ($confirmacoes as $botao) {
            $this->assertSame('button', $botao->getAttribute('type'));
            $this->assertSame('submitting', $botao->getAttribute('x-bind:disabled'));
            $this->assertSame('confirmLabel', $botao->getAttribute('x-text'));
            $porVariante[$botao->getAttribute('x-show')] = $this->classes($botao);
        }
        $this->assertContains('bg-danger', $porVariante["variant === 'danger'"]);
        $this->assertContains('bg-institutional', $porVariante["variant !== 'danger'"]);
        $this->assertSame(0, $xpath->query('.//*[contains(@class, "focus:ring")]', $dialogo)->length);
    }

    public function test_replace_original_modal_and_file_menu_keep_their_contracts(): void
    {
        $admin = $this->admin();
        $fotografia = $this->fotografiaComOriginal($admin);
        $html = $this->actingAs($admin)->get(route('admin.fotografias.show', $fotografia))->assertOk()->getContent();
        $xpath = $this->xpath($html);

        // Menu de ações: mesmo gatilho e Alpine; superfície e itens comuns.
        $gatilho = $xpath->query('//button[@id="arquivo-acoes-botao"]')->item(0);
        foreach (['x-ref' => 'actionsTrigger', ':aria-expanded' => 'open', 'aria-expanded' => 'false', 'aria-controls' => 'arquivo-acoes', 'aria-label' => 'Ações do arquivo'] as $atributo => $valor) {
            $this->assertSame($valor, $gatilho->getAttribute($atributo), $atributo);
        }
        $this->assertAtributosAlpine($html, 'id="arquivo-acoes-botao"', ['@click' => 'open = !open'], '<button');
        $this->assertStringContainsString('@keydown.escape="if (open) { open = false; $refs.actionsTrigger.focus() }"', $html);
        $menu = $xpath->query('//*[@id="arquivo-acoes"]')->item(0);
        $this->assertContains('admin-menu', $this->classes($menu));
        $this->assertSame('open', $menu->getAttribute('x-show'));
        $itens = $xpath->query('.//*[contains(concat(" ", @class, " "), " admin-menu-item ")]', $menu);
        $this->assertSame(2, $itens->length);
        $this->assertStringContainsString('@click="open = false; $dispatch(\'replace-original-open\')"', $html);
        $regenerar = $xpath->query('.//form', $menu)->item(0);
        $this->assertSame(route('admin.fotografias.regenerate-versions', $fotografia), $regenerar->getAttribute('action'));
        $this->assertSame('primary', $regenerar->getAttribute('data-confirm-variant'));

        // Modal: mesmo Alpine (abrir, fechar, restaurar a seleção do dropzone) e mesmo formulário.
        $dialogo = $xpath->query('//dialog[@id="substituir-arquivo-original"]')->item(0);
        $estado = $dialogo->parentNode;
        $this->assertStringContainsString("querySelector('[data-dropzone]')).clear()", $estado->getAttribute('x-data'));
        $this->assertStringContainsString('@replace-original-open.window="open()"', $html);
        $this->assertStringContainsString('@file-change="hasFile = $event.detail.file !== null"', $html);
        foreach (['x-ref' => 'dialog', 'aria-labelledby' => 'substituir-imagem-titulo', 'aria-describedby' => 'substituir-imagem-descricao'] as $atributo => $valor) {
            $this->assertSame($valor, $dialogo->getAttribute($atributo), $atributo);
        }
        $this->assertAtributosAlpine($html, 'id="substituir-arquivo-original"', ['@cancel' => 'if (submitting) $event.preventDefault()', '@close' => 'restore()'], '<dialog');
        $this->assertContains('admin-dialog', $this->classes($dialogo));

        $fechar = $xpath->query('.//button[@aria-label="Fechar"]', $dialogo)->item(0);
        $this->assertSame('button', $fechar->getAttribute('type'));
        $this->assertSame('close()', $fechar->getAttribute('x-on:click'));
        $this->assertSame('submitting', $fechar->getAttribute('x-bind:disabled'));

        $form = $xpath->query('.//form', $dialogo)->item(0);
        $this->assertSame(route('admin.fotografias.replace-original', $fotografia), $form->getAttribute('action'));
        $this->assertSame('multipart/form-data', $form->getAttribute('enctype'));
        $this->assertSame('PUT', $xpath->query('.//input[@name="_method"]', $form)->item(0)->getAttribute('value'));
        $this->assertStringContainsString('@submit="submitting = true"', $html);
        $this->assertSame(1, $xpath->query('.//*[@data-dropzone]//input[@name="arquivo_original"]', $form)->length);
        $confirmar = $xpath->query('.//button[@type="submit"]', $form)->item(0);
        $this->assertTrue($confirmar->hasAttribute('disabled'));
        $this->assertSame('!hasFile || submitting', $confirmar->getAttribute('x-bind:disabled'));
    }

    public function test_user_menu_keeps_alpine_contract_and_logout_form(): void
    {
        $html = $this->actingAs($this->admin())->get(route('admin.perfil.edit'))->assertOk()->getContent();
        $xpath = $this->xpath($html);

        $gatilho = $xpath->query('//button[@aria-controls="admin-user-menu"]')->item(0);
        foreach (['x-ref' => 'userTrigger', ':aria-expanded' => 'open', 'aria-expanded' => 'false', 'type' => 'button'] as $atributo => $valor) {
            $this->assertSame($valor, $gatilho->getAttribute($atributo), $atributo);
        }
        $this->assertAtributosAlpine($html, 'x-ref="userTrigger"', ['@click' => 'open = !open'], '<button');
        $this->assertStringContainsString('@keydown.escape.window="if (open) { open = false; $refs.userTrigger.focus() }"', $html);

        $menu = $xpath->query('//*[@id="admin-user-menu"]')->item(0);
        $this->assertContains('admin-menu', $this->classes($menu));
        $itens = $xpath->query('.//*[contains(concat(" ", @class, " "), " admin-menu-item ")]', $menu);
        $textos = array_map(fn (DOMElement $item) => trim(preg_replace('/\s+/', ' ', $item->textContent)), iterator_to_array($itens));
        $this->assertCount(3, $textos);
        $this->assertSame('Meu perfil', $textos[0]);
        $this->assertStringStartsWith('Página pública', $textos[1]);
        $this->assertStringEndsWith('(abre em nova aba)', $textos[1]);
        $this->assertSame('Sair', $textos[2]);
        $this->assertSame('page', $itens->item(0)->getAttribute('aria-current'));
        $sair = $xpath->query('.//form[@action="'.route('logout').'"]', $menu)->item(0);
        $this->assertSame('POST', $sair->getAttribute('method'));
        $this->assertSame(1, $xpath->query('.//input[@name="_token"]', $sair)->length);
        $this->assertSame('submit', $xpath->query('.//button', $sair)->item(0)->getAttribute('type'));

        $this->assertContains('admin-menu', $this->classes($xpath->query('//nav[@aria-label="Navegação administrativa mobile"]')->item(0)));
    }

    public function test_quick_create_modal_keeps_livewire_contract_and_links_errors(): void
    {
        Livewire::actingAs($this->admin());

        $componente = Livewire::test(CatalogPicker::class, ['catalog' => 'categoria', 'initialSelected' => null]);
        $xpath = $this->xpath($componente->html());

        $raiz = $xpath->query('//*[@x-data="quickCatalogModal()"]')->item(0);
        $this->assertStringContainsString('@quick-catalog-created.window="if ($event.detail.catalog === \'categoria\') close()"', $componente->html());
        $this->assertStringContainsString('@open-catalog-create.window="if ($event.detail.catalog === \'categoria\') open()"', $componente->html());
        $this->assertSame('open()', $xpath->query('.//button[@x-ref="trigger"]', $raiz)->item(0)->getAttribute('x-on:click'));
        $template = $xpath->query('.//template', $raiz)->item(0);
        $this->assertSame('body', $template->getAttribute('x-teleport'));

        $dialogo = $this->dialogoDoTemplate($componente->html());
        foreach (['x-ref' => 'dialog', 'wire:ignore.self' => '', 'aria-labelledby' => 'quick-title-categoria'] as $atributo => $valor) {
            $this->assertTrue($dialogo->hasAttribute($atributo), $atributo);
            $this->assertSame($valor, $dialogo->getAttribute($atributo), $atributo);
        }
        $this->assertAtributosAlpine($componente->html(), 'aria-labelledby="quick-title-categoria"', ['@close' => 'restoreFocus()'], '<dialog');
        $this->assertContains('admin-dialog', $this->classes($dialogo));
        $fechar = $dialogo->getElementsByTagName('button')->item(0);
        $this->assertSame('Fechar cadastro', $fechar->getAttribute('aria-label'));
        $this->assertSame('close()', $fechar->getAttribute('x-on:click'));
        $campo = $this->porId($dialogo, 'quick-categoria-titulo');
        $this->assertSame('form.titulo', $campo->getAttribute('wire:model'));
        $this->assertSame('save', $campo->getAttribute('wire:keydown.enter.prevent'));
        $this->assertFalse($campo->hasAttribute('aria-invalid'));

        // Erro do Livewire: campo inválido e descrito pela mensagem, que fica fora do rótulo.
        $componente->set('form.titulo', '')->call('save')->assertHasErrors(['form.titulo' => 'required']);
        $dialogo = $this->dialogoDoTemplate($componente->html());
        $campo = $this->porId($dialogo, 'quick-categoria-titulo');
        $this->assertSame('true', $campo->getAttribute('aria-invalid'));
        $this->assertSame('quick-categoria-titulo-erro', $campo->getAttribute('aria-describedby'));
        $erro = $this->porId($dialogo, 'quick-categoria-titulo-erro');
        $this->assertSame('alert', $erro->getAttribute('role'));
        $this->assertNotSame('', trim($erro->textContent));
        $this->assertNotSame('label', $erro->parentNode->nodeName);

        $salvar = null;
        foreach ($dialogo->getElementsByTagName('button') as $botao) {
            if ($botao->getAttribute('wire:click') === 'save') {
                $salvar = $botao;
            }
        }
        $this->assertInstanceOf(DOMElement::class, $salvar);
        $this->assertSame('disabled', $salvar->getAttribute('wire:loading.attr'));
        $this->assertSame('save', $salvar->getAttribute('wire:target'));
    }

    /**
     * Atributos com @ (atalhos do Alpine) não são nomes válidos para o DOMDocument, que os descarta:
     * por isso são conferidos na tag de abertura, no HTML bruto, a partir de um trecho único dela.
     *
     * @param  array<string, string>  $atributos
     */
    private function assertAtributosAlpine(string $html, string $trecho, array $atributos, string $abertura = '<'): void
    {
        $posicao = strpos($html, $trecho);
        $this->assertNotFalse($posicao, "Trecho não encontrado: {$trecho}");
        $inicio = strrpos(substr($html, 0, $posicao + strlen($trecho)), $abertura);
        $tag = substr($html, $inicio, strpos($html, '>', $posicao) - $inicio);

        foreach ($atributos as $atributo => $valor) {
            $this->assertStringContainsString($atributo.'="'.$valor.'"', $tag, "{$atributo} em {$trecho}");
        }
    }

    private function dialogoDoTemplate(string $html): DOMElement
    {
        // O conteúdo de <template> não entra na árvore do DOMDocument: o diálogo é lido à parte.
        $this->assertSame(1, preg_match('/<template x-teleport="body">(.*?)<\/template>/s', $html, $partes));
        $dialogo = $this->xpath($partes[1])->query('//dialog')->item(0);
        $this->assertInstanceOf(DOMElement::class, $dialogo);

        return $dialogo;
    }

    private function porId(DOMElement $contexto, string $id): DOMElement
    {
        $elemento = (new DOMXPath($contexto->ownerDocument))->query(sprintf('.//*[@id="%s"]', $id), $contexto)->item(0);
        $this->assertInstanceOf(DOMElement::class, $elemento, $id);

        return $elemento;
    }

    private function fotografiaComOriginal(User $autor): ItemAcervo
    {
        $this->actingAs($autor)->post(route('admin.fotografias.store'), [
            'titulo' => 'Praça central',
            'tipo_item' => 'fotografia',
            'tipo_data' => 'ano',
            'ano' => 1980,
            'estado_conservacao' => 'bom',
            'status' => 'rascunho',
            'visibilidade' => Visibilidade::Privado->value,
            'arquivo_original' => UploadedFile::fake()->image('original.jpg', 1200, 800),
        ])->assertSessionHasNoErrors();

        return ItemAcervo::query()->latest('id')->firstOrFail();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'ativo']);
    }

    private function dom(TestResponse $resposta): DOMXPath
    {
        return $this->xpath($resposta->getContent());
    }

    private function xpath(string $html): DOMXPath
    {
        $dom = new DOMDocument;
        @$dom->loadHTML('<?xml encoding="utf-8"?>'.(str_contains($html, '<html') ? $html : '<html><body>'.$html.'</body></html>'), LIBXML_NOERROR);

        return new DOMXPath($dom);
    }

    /** @return list<string> */
    private function classes(?DOMElement $elemento): array
    {
        $this->assertInstanceOf(DOMElement::class, $elemento);

        return preg_split('/\s+/', trim($elemento->getAttribute('class')));
    }
}
