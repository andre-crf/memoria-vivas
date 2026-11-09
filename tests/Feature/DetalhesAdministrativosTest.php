<?php

namespace Tests\Feature;

use App\Auditing\AuditContext;
use App\Auditing\AuditEventData;
use App\Auditing\AuditRecorder;
use App\Auditing\Enums\AuditAction;
use App\Auditing\Enums\AuditEntity;
use App\Auditing\Enums\AuditSource;
use App\Enums\StatusDerivacoes;
use App\Enums\Visibilidade;
use App\Jobs\GerarVersoesOtimizadas;
use App\Models\Arquivo;
use App\Models\AuditEvent;
use App\Models\Contribuicao;
use App\Models\ItemAcervo;
use App\Models\User;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Páginas de detalhes e painel com x-admin.page-header, x-catalog.section, x-admin.info-block e badges (Etapa 6A):
 * a apresentação mudou; ações, permissões, estados de processamento, menu e diálogo continuam os mesmos.
 */
class DetalhesAdministrativosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_section_supports_flush_panels_and_labelled_titles(): void
    {
        view()->share('errors', new ViewErrorBag);

        $xpath = $this->xpath((string) $this->blade('<x-catalog.section flush title="Histórico" title-id="historico-titulo" description="Eventos."><ul><li>1</li></ul></x-catalog.section>'));
        $secao = $xpath->query('//section')->item(0);
        $this->assertSame('historico-titulo', $secao->getAttribute('aria-labelledby'));
        $this->assertSame('historico-titulo', $xpath->query('//header//h2')->item(0)->getAttribute('id'));
        $this->assertContains('overflow-hidden', $this->classes($secao));
        $this->assertNotContains('p-5', $this->classes($secao));
        $this->assertContains('px-5', $this->classes($xpath->query('//header')->item(0)));

        // Sem flush nem title-id: o mesmo bloco dos formulários, sem aria-labelledby.
        $xpath = $this->xpath((string) $this->blade('<x-catalog.section title="Dados"><p>Conteúdo</p></x-catalog.section>'));
        $secao = $xpath->query('//section')->item(0);
        $this->assertFalse($secao->hasAttribute('aria-labelledby'));
        $this->assertFalse($xpath->query('//h2')->item(0)->hasAttribute('id'));
        $this->assertContains('p-5', $this->classes($secao));
        $this->assertContains('mb-5', $this->classes($xpath->query('//header')->item(0)));
    }

    public function test_photo_status_badge_uses_semantic_tones(): void
    {
        view()->share('errors', new ViewErrorBag);

        foreach (['rascunho' => 'bg-mist-150', 'em_revisao' => 'bg-institutional-soft', 'publicado' => 'bg-success-soft', 'arquivado' => 'bg-mist-150'] as $status => $classe) {
            $fotografia = new ItemAcervo(['status' => $status]);
            $badge = $this->xpath((string) $this->blade('<x-admin.status-fotografia :fotografia="$f" class="extra" />', ['f' => $fotografia]))->query('//span')->item(0);

            $this->assertSame(ItemAcervo::STATUS[$status], trim($badge->textContent), $status);
            $this->assertContains($classe, $this->classes($badge), $status);
            $this->assertContains('extra', $this->classes($badge), $status);
        }
    }

    public function test_photo_detail_keeps_actions_panels_menu_and_dialog(): void
    {
        $admin = $this->admin();
        $fotografia = $this->fotografiaComOriginal($admin, ['status' => 'publicado', 'visibilidade' => Visibilidade::Publico->value]);

        $xpath = $this->dom($this->actingAs($admin)->get(route('admin.fotografias.show', $fotografia))->assertOk());
        $cabecalho = $this->assertCabecalho($xpath, 'Praça central', 'Fotografia #'.$fotografia->id);
        $this->assertSame(route('admin.fotografias.index'), $xpath->query('.//a[normalize-space()="Voltar para fotografias"]', $cabecalho)->item(0)?->getAttribute('href'));
        $this->assertSame(['Publicado', 'Público'], $this->textos($xpath, './/span[contains(@class, "rounded-full")]', $cabecalho));
        $this->assertContains('bg-success-soft', $this->classes($xpath->query('.//span[normalize-space()="Publicado"]', $cabecalho)->item(0)));
        $this->assertSame(route('admin.fotografias.edit', $fotografia), $xpath->query('.//a[normalize-space()="Editar"]', $cabecalho)->item(0)?->getAttribute('href'));
        $excluir = $xpath->query(sprintf('.//form[@action="%s"]', route('admin.fotografias.destroy', $fotografia)), $cabecalho)->item(0);
        $this->assertSame('DELETE', $xpath->query('.//input[@name="_method"]', $excluir)->item(0)?->getAttribute('value'));
        $this->assertSame('Excluir fotografia', $excluir->getAttribute('data-confirm-label'));
        $this->assertSame('submit', $xpath->query('.//button', $excluir)->item(0)->getAttribute('type'));

        // Painéis no bloco padrão, na mesma ordem, com h2.
        $this->assertSame(['Dados de catalogação', 'Classificações', 'Pessoas e autoria', 'Histórico de auditoria', 'Auditoria', 'Arquivos'], $this->textos($xpath, '//main//section/header//h2'));
        $this->assertSame('photo-audit-history-title', $xpath->query('//main//section[.//h2[normalize-space()="Histórico de auditoria"]]')->item(0)->getAttribute('aria-labelledby'));
        $this->assertSame(1, $xpath->query('//section[@id="dados-catalogacao"]//dl')->length);

        // O menu de ações do arquivo fica no cabeçalho do painel Arquivos, com os mesmos atributos; o diálogo segue no painel.
        $arquivos = $xpath->query('//main//section[.//h2[normalize-space()="Arquivos"]]')->item(0);
        $menu = $xpath->query('./header//button[@id="arquivo-acoes-botao"]', $arquivos)->item(0);
        $this->assertInstanceOf(DOMElement::class, $menu);
        $this->assertSame('Ações do arquivo', $menu->getAttribute('aria-label'));
        $this->assertSame('arquivo-acoes', $menu->getAttribute('aria-controls'));
        $this->assertSame('actionsTrigger', $menu->getAttribute('x-ref'));
        $this->assertSame(1, $xpath->query(sprintf('./header//form[@action="%s"]', route('admin.fotografias.regenerate-versions', $fotografia)), $arquivos)->length);
        $dialogo = $xpath->query('.//dialog[@id="substituir-arquivo-original"]', $arquivos)->item(0);
        $this->assertInstanceOf(DOMElement::class, $dialogo);
        $this->assertSame('dialog', $dialogo->getAttribute('x-ref'));
        $this->assertSame(route('admin.fotografias.replace-original', $fotografia), $xpath->query('.//form', $dialogo)->item(0)->getAttribute('action'));

        $this->assertMarcacaoAntigaAusente($xpath);
    }

    public function test_photo_processing_states_keep_text_data_attribute_and_tone(): void
    {
        config(['acervo.optimized_processing.async' => true, 'queue.default' => 'database']);
        $admin = $this->admin();
        $fotografia = $this->fotografiaComOriginal($admin);

        $estados = [
            'pendente' => [StatusDerivacoes::Pendente, null, 'Pendente', 'bg-institutional-soft'],
            'processando' => [StatusDerivacoes::Processando, null, 'Em processamento', 'bg-institutional-soft'],
            'concluido' => [StatusDerivacoes::Concluido, null, 'Concluído', 'bg-success-soft'],
            'falhou' => [StatusDerivacoes::Falhou, null, 'Falhou', 'bg-error-soft'],
            'sem_progresso' => [StatusDerivacoes::Processando, now()->subSeconds(GerarVersoesOtimizadas::TIMEOUT + 1), 'Sem progresso', 'bg-warning-soft'],
        ];

        foreach ($estados as $atributo => [$status, $quando, $texto, $classe]) {
            Arquivo::query()->where('item_acervo_id', $fotografia->id)->where('versao_arquivo', 'original')
                ->update(['derivacoes_status' => $status->value, 'derivacoes_atualizadas_em' => $quando ?? now()]);

            $xpath = $this->dom($this->actingAs($admin)->get(route('admin.fotografias.show', $fotografia))->assertOk());
            $selo = $xpath->query(sprintf('//*[@data-status-derivacoes="%s"]', $atributo))->item(0);

            $this->assertInstanceOf(DOMElement::class, $selo, $atributo);
            $this->assertSame($texto, trim($selo->textContent), $atributo);
            $this->assertContains($classe, $this->classes($selo), $atributo);
            $this->assertSame(1, $xpath->query('.//svg[@aria-hidden="true"]', $selo)->length, "{$atributo}: ícone decorativo");
        }
    }

    public function test_operator_keeps_the_same_photo_actions_as_before(): void
    {
        $operador = User::factory()->create(['role' => 'operador', 'status' => 'ativo']);
        $fotografia = $this->fotografiaComOriginal($this->admin());

        // Editar, excluir e substituir o original são de qualquer usuário interno (políticas inalteradas).
        $xpath = $this->dom($this->actingAs($operador)->get(route('admin.fotografias.show', $fotografia))->assertOk());
        $this->assertSame(route('admin.fotografias.edit', $fotografia), $xpath->query('//main//header//a[normalize-space()="Editar"]')->item(0)?->getAttribute('href'));
        $this->assertSame(1, $xpath->query(sprintf('//main//header//form[@action="%s"]', route('admin.fotografias.destroy', $fotografia)))->length);
        $this->assertSame(1, $xpath->query('//button[@id="arquivo-acoes-botao"]')->length);
        $this->assertSame(1, $xpath->query('//dialog[@id="substituir-arquivo-original"]')->length);
    }

    public function test_audit_detail_keeps_navigation_labels_and_preformatted_values(): void
    {
        $admin = $this->admin();
        $evento = $this->eventoAuditoria($admin);
        $navegacao = ['acao' => 'created', 'page' => 2];

        $resposta = $this->actingAs($admin)->get(route('admin.auditoria.show', ['evento' => $evento, ...$navegacao]))->assertOk();
        $xpath = $this->dom($resposta);
        $cabecalho = $this->assertCabecalho($xpath, AuditAction::Created->label(), 'Evento de auditoria #'.$evento->id);
        $voltar = $xpath->query('.//a[normalize-space()="Voltar para auditoria"]', $cabecalho)->item(0);
        $this->assertSame(route('admin.auditoria.index', $navegacao), $voltar->getAttribute('href'));
        $this->assertSame([AuditEntity::Categoria->label()], $this->textos($xpath, './/span[contains(@class, "rounded-full")]', $cabecalho));

        foreach (['audit-differences-title' => 'Alterações registradas', 'audit-metadata-title' => 'Contexto da operação'] as $id => $titulo) {
            $this->assertSame($titulo, trim($xpath->query(sprintf('//section[@aria-labelledby="%s"]//h2[@id="%s"]', $id, $id))->item(0)?->textContent ?? ''));
        }
        $tabela = $xpath->query('//section[@aria-labelledby="audit-differences-title"]//table[contains(@class, "admin-table")]')->item(0);
        $this->assertInstanceOf(DOMElement::class, $tabela);
        $this->assertSame(3, $xpath->query('.//thead/tr/th[@scope="col"]', $tabela)->length);
        $this->assertSame(1, $xpath->query('.//tbody/tr/th[@scope="row"]', $tabela)->length);
        $resposta->assertSee('whitespace-pre-line', false)->assertSee('break-words', false);

        $this->assertSame(['Evento', 'Entidade afetada', 'Identificadores técnicos'], $this->textos($xpath, '//aside//section/header//h2'));
        $this->assertContains('Request ID', $this->textos($xpath, '//aside//dt'));
        $this->assertMarcacaoAntigaAusente($xpath);
    }

    public function test_contribution_detail_keeps_data_and_downloads(): void
    {
        $contribuicao = Contribuicao::create(['nome' => 'Ana', 'email' => 'ana@memorias.test', 'titulo' => 'Fotos da praça', 'autorizacao' => true]);
        $arquivo = $contribuicao->arquivos()->create(['nome_original' => 'praca.jpg', 'caminho' => 'contribuicoes/praca.jpg', 'mime_type' => 'image/jpeg', 'tamanho' => 2_097_152]);

        $xpath = $this->dom($this->actingAs($this->admin())->get(route('admin.contribuicoes.show', $contribuicao))->assertOk());
        $cabecalho = $this->assertCabecalho($xpath, 'Fotos da praça', 'Contribuição recebida');
        $this->assertSame(route('admin.contribuicoes.index'), $xpath->query('.//a[normalize-space()="Voltar às contribuições"]', $cabecalho)->item(0)?->getAttribute('href'));
        $this->assertStringContainsString('Recebida em', $cabecalho->textContent);
        $this->assertSame(['Colaborador', 'E-mail', 'Local', 'Época'], $this->textos($xpath, '//main//dt'));
        $this->assertSame('mailto:ana@memorias.test', $xpath->query('//main//dd/a')->item(0)->getAttribute('href'));
        $this->assertSame(['Dados do envio', 'História e contexto', 'Fotografias enviadas'], $this->textos($xpath, '//main//section/header//h2'));

        $baixar = $xpath->query(sprintf('//main//a[@href="%s"]', route('admin.contribuicoes.arquivos.download', [$contribuicao, $arquivo])))->item(0);
        $this->assertInstanceOf(DOMElement::class, $baixar);
        $this->assertSame('Baixar imagem praca.jpg', trim(preg_replace('/\s+/', ' ', $baixar->textContent)));
        $this->assertMarcacaoAntigaAusente($xpath);
    }

    public function test_dashboard_keeps_metrics_links_and_empty_states(): void
    {
        $admin = $this->admin();

        $xpath = $this->dom($this->actingAs($admin)->get(route('admin.dashboard'))->assertOk());
        $this->assertCabecalho($xpath, 'Olá, Administrador!', 'Dashboard');
        $indicadores = $xpath->query('//section[@aria-label="Indicadores do acervo"]/article');
        $this->assertSame(4, $indicadores->length);
        $this->assertSame(['Ver todos: itens do acervo', 'Ver todos: fotografias', 'Ver todos: usuários ativos'], $this->textos($xpath, '//section[@aria-label="Indicadores do acervo"]//a'));
        $this->assertSame(
            [route('admin.fotografias.index'), route('admin.fotografias.index'), route('admin.usuarios.index')],
            array_map(fn (DOMElement $link): string => $link->getAttribute('href'), iterator_to_array($xpath->query('//section[@aria-label="Indicadores do acervo"]//a'))),
        );
        // Só arquivos digitais ainda não tem módulo próprio.
        $this->assertSame(['Módulo em preparação'], $this->textos($xpath, '//section[@aria-label="Indicadores do acervo"]//article/p[normalize-space()="Módulo em preparação"]'));
        $cadastrar = $xpath->query('//main//header[div/h1]//a[normalize-space()="Cadastrar fotografia"]')->item(0);
        $this->assertInstanceOf(DOMElement::class, $cadastrar, 'Ação de cadastrar fotografia no cabeçalho do painel');
        $this->assertSame(route('admin.fotografias.create'), $cadastrar->getAttribute('href'));
        $this->assertSame(['Downloads', 'Tipos de arquivos', 'Itens por década', 'Itens mais recentes'], $this->textos($xpath, '//main//section/header//h2'));
        $this->assertSame(['Nenhum download registrado', 'Nenhum item cadastrado'], $this->textos($xpath, '//main//section//h3'));
        $this->assertSame(route('admin.downloads.index'), $xpath->query('//main//section/header//a[normalize-space()="Ver relatório"]')->item(0)?->getAttribute('href'));
        $this->assertSame(route('admin.fotografias.index'), $xpath->query('//main//section/header//a[normalize-space()="Ver fotografias"]')->item(0)?->getAttribute('href'));

        $operador = User::factory()->create(['role' => 'operador', 'status' => 'ativo']);
        $this->fotografiaComOriginal($admin, ['status' => 'em_revisao']);
        $xpath = $this->dom($this->actingAs($operador)->get(route('admin.dashboard'))->assertOk());
        $this->assertSame(['Olá, Operador!'], $this->textos($xpath, '//h1'));
        $this->assertNotContains('Downloads', $this->textos($xpath, '//main//section/header//h2'));
        // O operador cadastra fotografias, mas não gerencia usuários: o cartão fica sem link e sem "em preparação".
        $this->assertSame(['Ver todos: itens do acervo', 'Ver todos: fotografias'], $this->textos($xpath, '//section[@aria-label="Indicadores do acervo"]//a'));
        $this->assertSame([], $this->textos($xpath, '//section[@aria-label="Indicadores do acervo"]//a[@href="'.route('admin.usuarios.index').'"]'));
        $this->assertSame(1, $xpath->query('//section[@aria-label="Indicadores do acervo"]//article/p[normalize-space()="Módulo em preparação"]')->length);
        $this->assertSame(route('admin.fotografias.create'), $xpath->query('//main//header[div/h1]//a[normalize-space()="Cadastrar fotografia"]')->item(0)?->getAttribute('href'));
        $this->assertContains('bg-institutional-soft', $this->classes($xpath->query('//main//li//span[normalize-space()="Em revisão"]')->item(0)));
        $this->assertMarcacaoAntigaAusente($xpath);
    }

    private function assertCabecalho(DOMXPath $xpath, string $titulo, string $eyebrow): DOMElement
    {
        $this->assertSame([$titulo], $this->textos($xpath, '//h1'));
        $cabecalho = $xpath->query('//main//header[div/h1]')->item(0);
        $this->assertInstanceOf(DOMElement::class, $cabecalho, 'h1 fora do x-admin.page-header');
        $this->assertNotContains('font-serif', $this->classes($xpath->query('.//h1', $cabecalho)->item(0)));
        $this->assertSame($eyebrow, trim($xpath->query('./div/p[contains(@class, "text-heritage-700")]', $cabecalho)->item(0)?->textContent ?? ''));

        return $cabecalho;
    }

    private function assertMarcacaoAntigaAusente(DOMXPath $xpath): void
    {
        $html = $xpath->document->saveHTML($xpath->query('//main')->item(0));

        foreach (['stone-', 'red-', 'focus:ring', 'shadow-sm', 'font-serif', 'uppercase tracking-[0.12em]'] as $antigo) {
            $this->assertStringNotContainsString($antigo, $html, $antigo);
        }
    }

    /** @param  array<string, mixed>  $atributos */
    private function fotografiaComOriginal(User $autor, array $atributos = []): ItemAcervo
    {
        $this->actingAs($autor)->post(route('admin.fotografias.store'), $atributos + [
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

    private function eventoAuditoria(User $admin): AuditEvent
    {
        return app(AuditRecorder::class)->record(
            AuditContext::forUser($admin, AuditSource::Web, (string) Str::uuid()),
            new AuditEventData(
                action: AuditAction::Created,
                subjectType: AuditEntity::Categoria,
                subjectId: '1',
                subjectLabel: 'Praças',
                newValues: ['titulo' => "Praças\ne logradouros"],
            ),
        );
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

    /** @return list<string> */
    private function textos(DOMXPath $xpath, string $consulta, ?DOMElement $contexto = null): array
    {
        $textos = [];
        foreach ($xpath->query($consulta, $contexto) as $no) {
            $textos[] = trim(preg_replace('/\s+/', ' ', $no->textContent));
        }

        return $textos;
    }
}
