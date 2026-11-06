<?php

namespace Tests\Feature;

use App\Models\ItemAcervo;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Formulários e componentes públicos (Etapa 6C): campos com as classes públicas comuns, erros ligados aos
 * campos e resumo em x-ui.alert, com os mesmos nomes, ids, rotas e regras de envio de antes.
 */
class FormulariosPublicosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_back_link_and_button_components(): void
    {
        view()->share('errors', new ViewErrorBag);

        $xpath = $this->xpath((string) $this->blade('<x-public.back-link href="/fotografias/1">Voltar à fotografia</x-public.back-link>'));
        $link = $xpath->query('//a')->item(0);
        $this->assertSame('/fotografias/1', $link->getAttribute('href'));
        $this->assertSame('←', trim($xpath->query('./span[@aria-hidden="true"]', $link)->item(0)->textContent));
        // O contorno de foco é o da área pública (3px institucional), sem cor própria mais fraca.
        $this->assertStringNotContainsString('outline', $link->getAttribute('class'));

        $botao = $this->xpath((string) $this->blade('<x-public.button type="submit" disabled>Enviar</x-public.button>'))->query('//button')->item(0);
        $this->assertSame('submit', $botao->getAttribute('type'));
        foreach (['disabled:opacity-65', 'disabled:cursor-not-allowed', 'aria-busy:cursor-wait'] as $classe) {
            $this->assertContains($classe, $this->classes($botao), $classe);
        }
        $this->assertSame('button', $this->xpath((string) $this->blade('<x-public.button>Ok</x-public.button>'))->query('//button')->item(0)->getAttribute('type'));
    }

    public function test_download_form_keeps_contract_and_uses_public_field_pattern(): void
    {
        $fotografia = $this->fotografiaComDerivacao();

        $xpath = $this->dom($this->get(route('public.fotografias.download.create', $fotografia))->assertOk());
        $this->assertSame(['Baixar fotografia'], $this->textos($xpath, '//h1'));
        $this->assertContains('font-serif', $this->classes($xpath->query('//h1')->item(0)));
        $voltar = $xpath->query('//a[contains(normalize-space(), "Voltar à fotografia")]')->item(0);
        $this->assertSame(route('public.fotografias.show', $fotografia), $voltar->getAttribute('href'));

        $form = $xpath->query(sprintf('//form[@method="POST"][@action="%s"]', route('public.fotografias.download.store', $fotografia)))->item(0);
        $this->assertInstanceOf(DOMElement::class, $form);
        $this->assertStringContainsString('Campo obrigatório', $form->textContent);
        $campos = [
            'nome' => ['download-name', 'input', ['type' => 'text', 'autocomplete' => 'name', 'maxlength' => '120']],
            'email' => ['download-email', 'input', ['type' => 'email', 'autocomplete' => 'email', 'maxlength' => '255']],
            'perfil_download_id' => ['download-profile', 'select', []],
            'motivo_download_id' => ['download-reason', 'select', []],
            'finalidade' => ['download-purpose', 'textarea', ['minlength' => '10', 'maxlength' => '1000']],
        ];
        foreach ($campos as $nome => [$id, $tag, $atributos]) {
            $campo = $this->campo($xpath, $nome);
            $this->assertSame($tag, $campo->tagName, $nome);
            $this->assertSame($id, $campo->getAttribute('id'), $nome);
            $this->assertTrue($campo->hasAttribute('required'), $nome);
            $this->assertContains('public-control', $this->classes($campo), $nome);
            foreach ($atributos as $atributo => $valor) {
                $this->assertSame($valor, $campo->getAttribute($atributo), "{$nome}: {$atributo}");
            }
            $rotulo = $xpath->query(sprintf('//label[@for="%s"]', $id))->item(0);
            $this->assertContains('public-label', $this->classes($rotulo), $nome);
            $this->assertSame(1, $xpath->query('.//span[@aria-hidden="true"][contains(@class, "public-required")]', $rotulo)->length, "{$nome}: asterisco decorativo");
        }
        $aceite = $this->campo($xpath, 'aceite');
        $this->assertSame('checkbox', $aceite->getAttribute('type'));
        $this->assertSame('submit', $xpath->query('.//button', $form)->item(0)->getAttribute('type'));
    }

    public function test_download_validation_marks_fields_and_restores_values(): void
    {
        $fotografia = $this->fotografiaComDerivacao();

        $xpath = $this->dom($this->from(route('public.fotografias.download.create', $fotografia))->followingRedirects()
            ->post(route('public.fotografias.download.store', $fotografia), ['nome' => 'Ana Silva', 'email' => 'invalido', 'finalidade' => 'curta'])
            ->assertOk());

        $resumo = $xpath->query('//*[@role="alert"]')->item(0);
        $this->assertStringContainsString('Confira os campos indicados antes de continuar.', $resumo->textContent);
        $this->assertContains('bg-error-soft', $this->classes($resumo));

        foreach (['email' => 'download-email-error', 'perfil_download_id' => 'download-profile-error', 'motivo_download_id' => 'download-reason-error', 'finalidade' => 'download-purpose-error', 'aceite' => 'download-accept-error'] as $nome => $idErro) {
            $this->assertCampoInvalido($xpath, $nome, $idErro);
        }
        $this->assertFalse($this->campo($xpath, 'nome')->hasAttribute('aria-invalid'));
        $this->assertSame('Ana Silva', $this->campo($xpath, 'nome')->getAttribute('value'));
        $this->assertSame('curta', trim($this->campo($xpath, 'finalidade')->textContent));
    }

    public function test_contribution_form_keeps_alpine_contract(): void
    {
        $html = $this->get(route('public.colaborar'))->assertOk()->getContent();
        $xpath = $this->xpath($html);

        $form = $xpath->query(sprintf('//form[@action="%s"]', route('public.colaborar.store')))->item(0);
        $this->assertSame('POST', $form->getAttribute('method'));
        $this->assertSame('multipart/form-data', $form->getAttribute('enctype'));
        $this->assertSame('contributionForm()', $form->getAttribute('x-data'));
        $this->assertStringContainsString('@submit="submitting = true"', $html);
        $this->assertStringContainsString('@change="selectFiles($event)"', $html);
        $fotos = $this->campo($xpath, 'fotos[]');
        $this->assertSame('contribution-photos', $fotos->getAttribute('id'));
        $this->assertTrue($fotos->hasAttribute('multiple'));
        $this->assertSame('image/jpeg,image/png,image/webp', $fotos->getAttribute('accept'));

        // O envio desabilita o botão e marca a espera (aria-busy), sem mudar o mecanismo.
        $enviar = $xpath->query('.//button[@type="submit"]', $form)->item(0);
        $this->assertSame('submitting', $enviar->getAttribute(':disabled'));
        $this->assertSame('submitting', $enviar->getAttribute(':aria-busy'));

        foreach (['nome', 'email', 'titulo', 'local', 'periodo', 'historia'] as $nome) {
            $this->assertContains('public-control', $this->classes($this->campo($xpath, $nome)), $nome);
        }
        $this->assertStringContainsString('Campo obrigatório', $form->textContent);
    }

    public function test_contribution_validation_links_every_message_including_optional_fields(): void
    {
        $xpath = $this->dom($this->from(route('public.colaborar'))->followingRedirects()->post(route('public.colaborar.store'), [
            'nome' => 'Maria Silva',
            'email' => 'maria@example.com',
            'titulo' => '',
            'local' => str_repeat('a', 181),
            'periodo' => str_repeat('b', 101),
            'historia' => str_repeat('c', 3001),
            'fotos' => [UploadedFile::fake()->create('documento.pdf', 10, 'application/pdf')],
        ])->assertOk());

        $resumo = $xpath->query('//*[@role="alert"][contains(@class, "bg-error-soft")]')->item(0);
        $this->assertStringContainsString('Confira os campos indicados abaixo.', $resumo->textContent);
        $this->assertStringContainsString('selecionadas novamente', $resumo->textContent);

        foreach (['titulo' => 'contribution-title-error', 'local' => 'contribution-place-error', 'periodo' => 'contribution-period-error', 'historia' => 'contribution-story-error', 'autorizacao' => 'contribution-consent-error'] as $nome => $idErro) {
            $this->assertCampoInvalido($xpath, $nome, $idErro);
        }
        // Erro de cada arquivo (fotos.*) também descreve o campo de fotos.
        $fotos = $this->campo($xpath, 'fotos[]');
        $this->assertSame('true', $fotos->getAttribute('aria-invalid'));
        $this->assertSame('contribution-photos-items-error', $fotos->getAttribute('aria-describedby'));
        $this->assertSame('Envie imagens JPG, PNG ou WebP.', trim($xpath->query('//*[@id="contribution-photos-items-error"]')->item(0)->textContent));
        $this->assertSame('Maria Silva', $this->campo($xpath, 'nome')->getAttribute('value'));
    }

    public function test_consent_error_stays_next_to_its_checkbox(): void
    {
        $xpath = $this->dom($this->from(route('public.colaborar'))->followingRedirects()->post(route('public.colaborar.store'), ['nome' => 'Maria'])->assertOk());

        $caixa = $this->campo($xpath, 'autorizacao');
        $this->assertSame('true', $caixa->getAttribute('aria-invalid'));
        $this->assertSame('contribution-consent-error', $caixa->getAttribute('aria-describedby'));
        // Mensagem e caixa no mesmo bloco: a mensagem é a irmã seguinte da linha da caixa de seleção.
        $bloco = $caixa->parentNode->parentNode;
        $this->assertSame('contribution-consent-error', $xpath->query('./p', $bloco)->item(0)?->getAttribute('id'));
        $this->assertSame('Confirme que você tem permissão para compartilhar as fotografias.', trim($xpath->query('./p', $bloco)->item(0)->textContent));
    }

    public function test_contribution_success_uses_status_alert(): void
    {
        $xpath = $this->dom($this->withSession(['contribuicao_enviada' => true])->get(route('public.colaborar'))->assertOk());

        $aviso = $xpath->query('//*[@role="status"][contains(@class, "bg-success-soft")]')->item(0);
        $this->assertInstanceOf(DOMElement::class, $aviso);
        $this->assertStringContainsString('Fotografias recebidas.', $aviso->textContent);
        $this->assertSame(route('public.colaborar').'#enviar', $xpath->query('.//a', $aviso)->item(0)->getAttribute('href'));
    }

    public function test_catalog_search_error_marks_the_search_box(): void
    {
        $xpath = $this->dom($this->get(route('public.catalogo', ['q' => str_repeat('x', 201)])));

        $campo = $xpath->query('//input[@id="public-search"]')->item(0);
        $this->assertSame('true', $campo->getAttribute('aria-invalid'));
        $this->assertSame('catalog-search-error', $campo->getAttribute('aria-describedby'));
        $this->assertContains('public-field-error', $this->classes($xpath->query('//*[@id="catalog-search-error"]')->item(0)));
        $this->assertContains('has-[input[aria-invalid=true]]:border-error-strong', $this->classes($xpath->query('//form[@role="search"]')->item(0)));
    }

    private function assertCampoInvalido(DOMXPath $xpath, string $nome, string $idErro): void
    {
        $campo = $this->campo($xpath, $nome);
        $this->assertSame('true', $campo->getAttribute('aria-invalid'), $nome);
        $this->assertSame($idErro, $campo->getAttribute('aria-describedby'), $nome);
        $mensagem = $xpath->query(sprintf('//*[@id="%s"]', $idErro))->item(0);
        $this->assertInstanceOf(DOMElement::class, $mensagem, $idErro);
        $this->assertNotSame('', trim($mensagem->textContent));
        $this->assertContains('public-field-error', $this->classes($mensagem), $idErro);
    }

    private function fotografiaComDerivacao(): ItemAcervo
    {
        $fotografia = ItemAcervo::create(['titulo' => 'Praça central', 'tipo_item' => 'fotografia', 'status' => 'publicado', 'visibilidade' => 'publico']);
        foreach (['original', 'large'] as $versao) {
            $arquivo = $fotografia->arquivos()->create([
                'provider' => 'local',
                'storage_path' => "acervo/{$fotografia->id}/{$versao}.jpg",
                'nome_original' => $versao === 'original' ? 'original.jpg' : null,
                'mime_type' => 'image/jpeg',
                'file_size' => 100,
                'tipo_arquivo' => 'imagem',
                'versao_arquivo' => $versao,
            ]);
            Storage::disk('local')->put($arquivo->storage_path, "conteudo {$versao}");
        }

        return $fotografia;
    }

    private function campo(DOMXPath $xpath, string $nome): DOMElement
    {
        $campo = $xpath->query(sprintf('//form//*[@name="%s"]', $nome))->item(0);
        $this->assertInstanceOf(DOMElement::class, $campo, "Campo {$nome} não encontrado");

        return $campo;
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

    /** @return list<string> */
    private function textos(DOMXPath $xpath, string $consulta): array
    {
        $textos = [];
        foreach ($xpath->query($consulta) as $no) {
            $textos[] = trim(preg_replace('/\s+/', ' ', $no->textContent));
        }

        return $textos;
    }
}
