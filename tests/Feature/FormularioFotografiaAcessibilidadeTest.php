<?php

namespace Tests\Feature;

use App\Enums\Visibilidade;
use App\Models\ItemAcervo;
use App\Models\User;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Formulário de fotografias (Etapa 6C): erros ligados aos campos (aria-invalid e aria-describedby) sem
 * entrar no nome acessível, resumo em x-ui.alert e os mesmos contratos de Alpine, Livewire e envio.
 */
class FormularioFotografiaAcessibilidadeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_validation_errors_describe_fields_without_entering_their_names(): void
    {
        $this->actingAs($this->admin());

        $xpath = $this->dom($this->from(route('admin.fotografias.create'))->followingRedirects()->post(route('admin.fotografias.store'), [
            'titulo' => '',
            'codigo_referencia' => '***',
            'legenda' => 'Legenda preservada',
            'tipo_data' => 'ano',
            'ano' => 'abc',
            'estado_conservacao' => 'bom',
            'status' => 'rascunho',
            'visibilidade' => Visibilidade::Privado->value,
            'local_atual' => str_repeat('x', 256),
            'autor_id' => 999999,
        ])->assertOk());

        // Resumo: título fixo e só os erros sem campo próprio (o seletor de autoria).
        // O dropzone tem um alerta próprio de cliente (x-show), fora deste resumo.
        $resumo = $xpath->query('//main//*[@role="alert"][not(@x-show)]')->item(0);
        $this->assertSame('Revise os campos destacados.', trim($xpath->query('.//p[1]', $resumo)->item(0)->textContent));
        $this->assertCount(1, $xpath->query('.//li', $resumo));
        $this->assertSame(1, $xpath->query('//main//*[@role="alert"][not(@x-show)]')->length);

        foreach (['titulo' => 'Título', 'codigo_referencia' => 'Código de referência', 'ano' => 'Ano', 'local_atual' => 'Local atual'] as $campo => $rotulo) {
            $controle = $this->porId($xpath, $campo);
            $this->assertSame('true', $controle->getAttribute('aria-invalid'), $campo);
            $descricao = explode(' ', $controle->getAttribute('aria-describedby'));
            $this->assertContains("{$campo}-erro", $descricao, $campo);

            $mensagem = $this->porId($xpath, "{$campo}-erro");
            $this->assertSame('true', $mensagem->getAttribute('aria-hidden'), "{$campo}: fora do nome acessível");
            $this->assertNotSame('', trim($mensagem->textContent));

            // Nome acessível vindo do rótulo: só o texto do rótulo (e "obrigatório"), sem a mensagem.
            $nome = $this->nomePeloRotulo($xpath->query(sprintf('//label[@for="%s"]', $campo))->item(0), $controle);
            $this->assertStringStartsWith($rotulo, $nome, $campo);
            $this->assertStringNotContainsString(trim($mensagem->textContent), $nome, $campo);
        }

        // A dica continua descrevendo o código de referência, antes da mensagem de erro.
        $this->assertSame('codigo_referencia-dica codigo_referencia-erro', $this->porId($xpath, 'codigo_referencia')->getAttribute('aria-describedby'));
        $this->assertSame('true', $this->porId($xpath, 'codigo_referencia-dica')->getAttribute('aria-hidden'));
        $this->assertSame('localizacao_fisica-dica', $this->porId($xpath, 'localizacao_fisica')->getAttribute('aria-describedby'));

        // Valores preservados e campos sem erro sem aria-invalid.
        $this->assertSame('***', $this->porId($xpath, 'codigo_referencia')->getAttribute('value'));
        $this->assertSame('Legenda preservada', trim($this->porId($xpath, 'legenda')->textContent));
        $this->assertFalse($this->porId($xpath, 'legenda')->hasAttribute('aria-invalid'));
        $this->assertSame('ano', $this->selecionado($this->porId($xpath, 'tipo_data')));
        $this->assertSame(0, $xpath->query('//main//*[contains(@class, "stone-") or contains(@class, "red-")]')->length);
    }

    public function test_caption_counter_describes_the_field_without_entering_its_name(): void
    {
        $this->actingAs($this->admin());

        $xpath = $this->dom($this->get(route('admin.fotografias.create'))->assertOk());
        $legenda = $this->porId($xpath, 'legenda');
        $contador = $this->porId($xpath, 'legenda-contador');

        $this->assertSame('legenda-contador', $legenda->getAttribute('aria-describedby'));
        $this->assertSame('true', $contador->getAttribute('aria-hidden'));
        $this->assertSame('formattedCount', $contador->getAttribute('x-text'));
        $this->assertSame('0/5.000', trim($contador->textContent));
        $this->assertSame('Legenda/descrição', $this->nomePeloRotulo($xpath->query('//label[@for="legenda"]')->item(0), $legenda));

        // Com erro, a mensagem vem depois do contador na descrição.
        $xpath = $this->dom($this->from(route('admin.fotografias.create'))->followingRedirects()->post(route('admin.fotografias.store'), [
            'titulo' => 'Coreto', 'legenda' => str_repeat('a', 5001), 'tipo_data' => 'desconhecida', 'estado_conservacao' => 'bom', 'status' => 'rascunho', 'visibilidade' => Visibilidade::Privado->value,
        ])->assertOk());
        $this->assertSame('legenda-contador legenda-erro', $this->porId($xpath, 'legenda')->getAttribute('aria-describedby'));
    }

    public function test_errors_of_date_fields_hidden_by_the_precision_are_listed_in_the_summary(): void
    {
        $this->actingAs($this->admin());

        // Sem JavaScript (ou com a requisição alterada), o dia chega com a precisão "Ano": o campo fica oculto.
        $xpath = $this->dom($this->from(route('admin.fotografias.create'))->followingRedirects()->post(route('admin.fotografias.store'), [
            'titulo' => 'Coreto', 'tipo_data' => 'ano', 'ano' => '1980', 'dia' => '40', 'estado_conservacao' => 'bom', 'status' => 'rascunho', 'visibilidade' => Visibilidade::Privado->value,
        ])->assertOk());

        $itens = array_map(fn (DOMNode $no) => trim(preg_replace('/\s+/', ' ', $no->textContent)), iterator_to_array($xpath->query('//main//*[@role="alert"][not(@x-show)]//li')));
        $this->assertContains('Dia (campo que não aparece com a precisão “Ano”): O dia deve estar entre 1 e 31.', $itens);
        $this->assertSame("isVisible('dia')", $xpath->query('//label[@for="dia"]')->item(0)->getAttribute('x-show'));

        // Campo de data visível com erro: a mensagem fica junto dele, não se repete no resumo.
        $xpath = $this->dom($this->from(route('admin.fotografias.create'))->followingRedirects()->post(route('admin.fotografias.store'), [
            'titulo' => 'Coreto', 'tipo_data' => 'ano', 'ano' => 'abc', 'estado_conservacao' => 'bom', 'status' => 'rascunho', 'visibilidade' => Visibilidade::Privado->value,
        ])->assertOk());
        $this->assertSame(0, $xpath->query('//main//*[@role="alert"][not(@x-show)]//li[contains(., "Ano")]')->length);
        $this->assertSame('true', $this->porId($xpath, 'ano')->getAttribute('aria-invalid'));
    }

    public function test_original_file_error_is_linked_to_the_dropzone_input(): void
    {
        $this->actingAs($this->admin());

        $xpath = $this->dom($this->from(route('admin.fotografias.create'))->followingRedirects()->post(route('admin.fotografias.store'), [
            'titulo' => 'Coreto',
            'tipo_data' => 'desconhecida',
            'estado_conservacao' => 'bom',
            'status' => 'rascunho',
            'visibilidade' => Visibilidade::Privado->value,
            'arquivo_original' => UploadedFile::fake()->create('planilha.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
        ])->assertOk());
        $input = $this->porId($xpath, 'arquivo_original');
        $this->assertSame('true', $input->getAttribute('aria-invalid'));
        $this->assertSame('arquivo_original-formatos arquivo_original-publicacao arquivo_original-erro', $input->getAttribute('aria-describedby'));
        $this->assertNotSame('', trim($this->porId($xpath, 'arquivo_original-erro')->textContent));
        $this->assertSame(0, $xpath->query('//main//*[@role="alert"][not(@x-show)]//li')->length, 'Erro com campo próprio não se repete no resumo');

        // Sem erro, o dropzone fica como antes.
        $xpath = $this->dom($this->get(route('admin.fotografias.create'))->assertOk());
        $input = $this->porId($xpath, 'arquivo_original');
        $this->assertFalse($input->hasAttribute('aria-invalid'));
        $this->assertSame('arquivo_original-formatos arquivo_original-publicacao', $input->getAttribute('aria-describedby'));
        $this->assertSame(0, $xpath->query('//*[@id="arquivo_original-erro"]')->length);
    }

    public function test_alpine_livewire_and_upload_contracts_are_preserved(): void
    {
        $this->actingAs($this->admin());

        $html = $this->get(route('admin.fotografias.create'))->assertOk()->getContent();
        $xpath = $this->xpath($html);

        $form = $xpath->query(sprintf('//form[@action="%s"]', route('admin.fotografias.store')))->item(0);
        $this->assertSame('POST', $form->getAttribute('method'));
        $this->assertSame('multipart/form-data', $form->getAttribute('enctype'));
        $this->assertSame('1', $xpath->query('.//input[@name="classificacoes_enviadas"]', $form)->item(0)->getAttribute('value'));

        $this->assertSame(1, $xpath->query('//*[starts-with(@x-data, "historicalDateFields(")]')->length);
        $this->assertSame('precision', $this->porId($xpath, 'tipo_data')->getAttribute('x-model'));
        foreach (['dia', 'mes', 'ano', 'decada'] as $campo) {
            $controle = $this->porId($xpath, $campo);
            $this->assertSame($campo, $controle->getAttribute('name'));
            $this->assertSame($campo, $controle->getAttribute('data-date-field'));
            $this->assertSame("!isVisible('{$campo}')", $controle->getAttribute(':disabled'));
            $this->assertSame("isVisible('{$campo}')", $xpath->query(sprintf('//label[@for="%s"]', $campo))->item(0)->getAttribute('x-show'));
        }
        $this->assertSame('characterCounter(0, 5000)', $xpath->query('//label[@for="legenda"]')->item(0)->getAttribute('x-data'));
        $this->assertSame('update($event.target.value)', $this->porId($xpath, 'legenda')->getAttribute('x-on:input'));
        $this->assertSame(1, $xpath->query('//*[starts-with(@x-data, "fileDropzone(")]')->length);
        $this->assertSame(1, $xpath->query('//input[@id="arquivo_original"][@name="arquivo_original"][@type="file"]')->length);
        foreach (['autor', 'pessoa', 'categoria', 'assunto', 'palavra-chave'] as $catalogo) {
            $this->assertSame(1, $xpath->query(sprintf('//*[@data-testid="catalog-picker-%s"]', $catalogo))->length, $catalogo);
        }
        $this->assertStringContainsString('wire:snapshot', $html);

        // Cabeçalho padrão, sem o resumo de erros quando não há erros.
        $this->assertSame(['Cadastrar fotografia'], array_map('trim', array_map(fn (DOMNode $no) => $no->textContent, iterator_to_array($xpath->query('//main//header//h1')))));
        $this->assertSame(route('admin.fotografias.index'), $xpath->query('//main//header//a[normalize-space()="Voltar para fotografias"]')->item(0)?->getAttribute('href'));
        $this->assertSame(0, $xpath->query('//main//*[@role="alert" and not(@x-show)]')->length);
    }

    public function test_edit_page_uses_the_standard_header(): void
    {
        $admin = $this->admin();
        $fotografia = ItemAcervo::create(['titulo' => 'Coreto', 'tipo_item' => 'fotografia', 'tipo_data' => 'desconhecida', 'status' => 'rascunho', 'visibilidade' => Visibilidade::Privado]);

        $xpath = $this->dom($this->actingAs($admin)->get(route('admin.fotografias.edit', $fotografia))->assertOk());
        $this->assertSame('Editar fotografia', trim($xpath->query('//main//header//h1')->item(0)->textContent));
        $this->assertSame(route('admin.fotografias.show', $fotografia), $xpath->query('//main//header//a[normalize-space()="Voltar para detalhes"]')->item(0)?->getAttribute('href'));
        $this->assertSame('Coreto', $this->porId($xpath, 'titulo')->getAttribute('value'));
        $this->assertSame(0, $xpath->query('//main//*[contains(@class, "stone-") or contains(@class, "red-")]')->length);
    }

    /**
     * Nome acessível vindo de um <label> que envolve o campo: o texto do rótulo, sem descendentes aria-hidden
     * e sem o próprio campo (regra de cálculo de nome acessível usada pelos navegadores).
     */
    private function nomePeloRotulo(DOMElement $rotulo, DOMElement $controle): string
    {
        $texto = function (DOMNode $no) use (&$texto, $controle): string {
            if ($no instanceof DOMElement && ($no->getAttribute('aria-hidden') === 'true' || $no->isSameNode($controle))) {
                return '';
            }
            if ($no->nodeType === XML_TEXT_NODE) {
                return $no->textContent;
            }
            $partes = '';
            foreach ($no->childNodes as $filho) {
                $partes .= $texto($filho);
            }

            return $partes;
        };

        return trim(preg_replace('/\s+/', ' ', $texto($rotulo)));
    }

    private function selecionado(DOMElement $select): string
    {
        foreach ($select->getElementsByTagName('option') as $opcao) {
            if ($opcao->hasAttribute('selected')) {
                return $opcao->getAttribute('value');
            }
        }

        return '';
    }

    private function porId(DOMXPath $xpath, string $id): DOMElement
    {
        $elemento = $xpath->query(sprintf('//*[@id="%s"]', $id))->item(0);
        $this->assertInstanceOf(DOMElement::class, $elemento, $id);

        return $elemento;
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
        @$dom->loadHTML('<?xml encoding="utf-8"?>'.$html, LIBXML_NOERROR);

        return new DOMXPath($dom);
    }
}
