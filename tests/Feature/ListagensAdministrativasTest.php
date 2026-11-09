<?php

namespace Tests\Feature;

use App\Auditing\AuditContext;
use App\Auditing\AuditEventData;
use App\Auditing\AuditRecorder;
use App\Auditing\Enums\AuditAction;
use App\Auditing\Enums\AuditEntity;
use App\Auditing\Enums\AuditSource;
use App\Enums\Visibilidade;
use App\Livewire\Admin\AuditEventList;
use App\Models\Assunto;
use App\Models\AuditEvent;
use App\Models\Autor;
use App\Models\Categoria;
use App\Models\Contribuicao;
use App\Models\ItemAcervo;
use App\Models\PalavraChave;
use App\Models\Pessoa;
use App\Models\User;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Listagens administrativas com x-admin.table, x-admin.page-header, badges e estados vazios (Etapa 5):
 * a apresentação mudou, a busca, a ordenação, a paginação e as ações por linha continuam as mesmas.
 */
class ListagensAdministrativasTest extends TestCase
{
    use RefreshDatabase;

    public function test_table_component_wraps_table_scroll_and_pagination(): void
    {
        view()->share('errors', new ViewErrorBag);
        $paginas = new LengthAwarePaginator(range(1, 10), 30, 10, 2, ['path' => '/exemplo']);
        $umaPagina = new LengthAwarePaginator([1], 1, 10, 1, ['path' => '/exemplo']);

        $xpath = $this->xpath((string) $this->blade(
            '<x-admin.table :paginator="$p" table-class="min-w-[65rem]" wire:loading.class="opacity-60"><thead><tr><th scope="col">A</th></tr></thead><tbody><tr><td>1</td></tr></tbody></x-admin.table>',
            ['p' => $paginas],
        ));
        $raiz = $xpath->query('/html/body/div')->item(0);
        $this->assertSame('opacity-60', $raiz->getAttribute('wire:loading.class'));
        $this->assertContains('shadow-card', $this->classes($raiz));
        $tabela = $xpath->query('//div[contains(@class, "overflow-x-auto")]/table')->item(0);
        $this->assertInstanceOf(DOMElement::class, $tabela, 'A rolagem horizontal fica no contêiner da tabela');
        // Aviso de rolagem (develop, tabelas no mobile): antes da área com rolagem, escondido a partir de xl.
        $aviso = $xpath->query('/html/body/div/p[following-sibling::div[contains(@class, "overflow-x-auto")]]')->item(0);
        $this->assertSame('Deslize a tabela para ver as demais colunas.', trim($aviso?->textContent ?? ''));
        $this->assertContains('xl:hidden', $this->classes($aviso));
        // w-full no componente; a largura mínima vem só de table-class.
        $this->assertNotContains('min-w-full', $this->classes($tabela));
        $this->assertContains('admin-table', $this->classes($tabela));
        $this->assertContains('min-w-[65rem]', $this->classes($tabela));
        $this->assertSame(1, $xpath->query('//nav[@aria-label="Paginação"]')->length);
        $this->assertSame(0, $xpath->query('//table//nav')->length, 'A paginação fica fora da tabela');

        // Uma página só: sem rodapé de paginação.
        $xpath = $this->xpath((string) $this->blade('<x-admin.table :paginator="$p"><tbody><tr><td>1</td></tr></tbody></x-admin.table>', ['p' => $umaPagina]));
        $this->assertSame(0, $xpath->query('//nav')->length);
        $this->assertSame(0, $xpath->query('//div[contains(@class, "border-t")]')->length);

        // Vazia: o slot ocupa o contêiner no lugar da tabela.
        $xpath = $this->xpath((string) $this->blade('<x-admin.table :vazia="true"><x-slot:vazio><p id="vazio">Nada</p></x-slot:vazio><tbody></tbody></x-admin.table>'));
        $this->assertSame(0, $xpath->query('//table')->length);
        $this->assertSame(1, $xpath->query('/html/body/div/p[@id="vazio"]')->length);
        $this->assertSame(0, $xpath->query('//p[contains(., "Deslize a tabela")]')->length, 'Sem tabela, sem aviso de rolagem');
    }

    public function test_search_without_results_keeps_api_on_top_of_empty_state(): void
    {
        view()->share('errors', new ViewErrorBag);
        $xpath = $this->xpath((string) $this->blade('<x-admin.busca-sem-resultados action="/admin/categorias" mensagem="Nada para a pesquisa" dica="Tente outro termo." limpar="Limpar filtros" class="extra" />'));

        $this->assertSame('Nada para a pesquisa', trim($xpath->query('//h2')->item(0)->textContent));
        $this->assertSame('Tente outro termo.', trim($xpath->query('//p')->item(0)->textContent));
        $limpar = $xpath->query('//a')->item(0);
        $this->assertSame('/admin/categorias', $limpar->getAttribute('href'));
        $this->assertSame('Limpar filtros', trim($limpar->textContent));
        $this->assertContains('extra', $this->classes($xpath->query('/html/body/div')->item(0)));
    }

    public function test_every_listing_uses_the_shared_header_and_table(): void
    {
        $admin = $this->admin();
        $this->dadosDeExemplo($admin);
        $listagens = [
            'admin.fotografias.index' => 'Fotografias cadastradas',
            'admin.fotografias.trashed' => 'Lixeira de fotografias',
            'admin.categorias.index' => 'Categorias',
            'admin.assuntos.index' => 'Assuntos',
            'admin.palavras-chave.index' => 'Palavras-chave',
            'admin.autores.index' => 'Autores',
            'admin.pessoas.index' => 'Pessoas identificadas',
            'admin.usuarios.index' => 'Usuários',
            'admin.contribuicoes.index' => 'Contribuições recebidas',
            'admin.auditoria.index' => 'Auditoria do sistema',
        ];

        foreach ($listagens as $rota => $titulo) {
            $xpath = $this->dom($this->actingAs($admin)->get(route($rota))->assertOk());

            $this->assertCabecalho($xpath, $titulo, $rota);
            $tabela = $xpath->query('//main//table[contains(concat(" ", normalize-space(@class), " "), " admin-table ")]')->item(0);
            $this->assertInstanceOf(DOMElement::class, $tabela, "{$rota}: x-admin.table");

            // Cabeçalho associado às células: todo th do thead tem scope=col e cada linha tem uma célula por coluna.
            $colunas = $xpath->query('.//thead/tr/th', $tabela);
            $this->assertGreaterThan(0, $colunas->length, $rota);
            $this->assertSame($colunas->length, $xpath->query('.//thead/tr/th[@scope="col"]', $tabela)->length, "{$rota}: scope");
            foreach ($xpath->query('.//tbody/tr', $tabela) as $linha) {
                $this->assertSame($colunas->length, $xpath->query('./td', $linha)->length, "{$rota}: células por linha");
            }

            // Marcação antiga de tabela e de botão não volta.
            $html = $tabela->ownerDocument->saveHTML($tabela);
            $this->assertStringNotContainsString('stone-', $html, $rota);
            $this->assertStringNotContainsString('focus:ring-2', $html, $rota);
            $this->assertStringNotContainsString('red-', $html, $rota);
            $this->assertSame(0, $xpath->query('.//button[not(@type)]', $tabela)->length, "{$rota}: botão sem type");
        }
    }

    public function test_listings_keep_minimum_widths_and_scroll_hint_on_small_screens(): void
    {
        $admin = $this->admin();
        $this->dadosDeExemplo($admin);
        // Larguras definidas na develop (correção das tabelas no mobile): abaixo delas a tabela rola, sem espremer colunas.
        $larguras = [
            'admin.fotografias.index' => 'min-w-[72rem]',
            'admin.fotografias.trashed' => 'min-w-[72rem]',
            'admin.categorias.index' => 'min-w-[60rem]',
            'admin.assuntos.index' => 'min-w-[60rem]',
            'admin.pessoas.index' => 'min-w-[60rem]',
            'admin.palavras-chave.index' => 'min-w-[42rem]',
            'admin.autores.index' => 'min-w-[52rem]',
            'admin.usuarios.index' => 'min-w-[56rem]',
            'admin.contribuicoes.index' => 'min-w-[52rem]',
            'admin.auditoria.index' => 'min-w-[52rem]',
        ];

        foreach ($larguras as $rota => $largura) {
            $xpath = $this->dom($this->actingAs($admin)->get(route($rota))->assertOk());
            $tabela = $xpath->query('//main//table[contains(concat(" ", normalize-space(@class), " "), " admin-table ")]')->item(0);
            $this->assertContains($largura, $this->classes($tabela), $rota);
            $this->assertSame(1, $xpath->query('//main//p[contains(., "Deslize a tabela para ver as demais colunas.")]')->length, "{$rota}: aviso");
            $this->assertGreaterThan(0, $xpath->query('.//tbody/tr/td[contains(concat(" ", @class, " "), " min-w-56 ") or contains(concat(" ", @class, " "), " min-w-64 ")]', $tabela)->length, "{$rota}: coluna principal");
        }

        $evento = AuditEvent::query()->firstOrFail();
        $xpath = $this->dom($this->actingAs($admin)->get(route('admin.auditoria.show', $evento))->assertOk());
        $this->assertContains('min-w-[44rem]', $this->classes($xpath->query('//section[@aria-labelledby="audit-differences-title"]//table')->item(0)));
        $this->assertSame(1, $xpath->query('//section[@aria-labelledby="audit-differences-title"]//p[contains(., "Deslize a tabela")]')->length);
    }

    public function test_download_report_follows_admin_typography_and_keeps_export(): void
    {
        $this->actingAs($this->admin());

        $xpath = $this->dom($this->get(route('admin.downloads.index', ['aba' => 'registros']))->assertOk());
        $this->assertCabecalho($xpath, 'Relatório de downloads', 'downloads');
        $this->assertSame(0, $xpath->query('//main//*[contains(@class, "font-serif")]')->length, 'Admin é sans-serif');
        $this->assertSame(['Nenhum download encontrado ainda'], $this->textos($xpath, '//main//h3'));
        $this->assertSame(route('admin.downloads.export'), $xpath->query('//a[normalize-space()="Exportar CSV"]')->item(0)?->getAttribute('href'));

        // Com filtros: o estado vazio diz isso e oferece limpar; a exportação leva os mesmos filtros.
        $filtros = ['q' => 'Inexistente', 'de' => '2026-01-01'];
        $xpath = $this->dom($this->get(route('admin.downloads.index', $filtros + ['aba' => 'registros']))->assertOk());
        $this->assertSame(['Nenhum download encontrado para os filtros selecionados'], $this->textos($xpath, '//main//h3'));
        // Limpar filtros mantém a aba de registros.
        $this->assertSame(route('admin.downloads.index', ['aba' => 'registros']), $xpath->query('//main//a[normalize-space()="Limpar filtros"]')->item(0)?->getAttribute('href'));
        $this->assertSame(route('admin.downloads.export', $filtros), $xpath->query('//a[normalize-space()="Exportar CSV"]')->item(0)?->getAttribute('href'));
    }

    public function test_empty_listing_and_search_without_results_are_distinct(): void
    {
        $this->actingAs($this->admin());

        $xpath = $this->dom($this->get(route('admin.categorias.index'))->assertOk());
        $this->assertSame(['Nenhuma categoria cadastrada'], $this->textos($xpath, '//main//h2'));
        $this->assertSame(0, $xpath->query('//main//table')->length);
        $this->assertSame(0, $xpath->query('//main//a[normalize-space()="Limpar pesquisa"]')->length);

        Categoria::create(['titulo' => 'Praças']);
        $xpath = $this->dom($this->get(route('admin.categorias.index', ['q' => 'inexistente', 'ordem' => 'itens', 'direcao' => 'asc']))->assertOk());
        $this->assertSame(['Nenhuma categoria encontrada para a pesquisa'], $this->textos($xpath, '//main//h2'));
        $this->assertSame(route('admin.categorias.index'), $xpath->query('//main//a[normalize-space()="Limpar pesquisa"]')->item(0)?->getAttribute('href'));

        // Fotografias: filtros sem resultado oferecem limpar os filtros.
        $xpath = $this->dom($this->get(route('admin.fotografias.index', ['status' => 'publicado']))->assertOk());
        $this->assertSame(['Nenhuma fotografia encontrada para os filtros aplicados'], $this->textos($xpath, '//main//h2'));
        $this->assertSame(route('admin.fotografias.index'), $xpath->query('//main//a[normalize-space()="Limpar filtros"]')->item(0)?->getAttribute('href'));

        $xpath = $this->dom($this->get(route('admin.contribuicoes.index'))->assertOk());
        $this->assertSame(['Nenhuma contribuição recebida ainda'], $this->textos($xpath, '//main//h2'));
    }

    public function test_status_and_visibility_use_semantic_badges(): void
    {
        $this->actingAs($this->admin());
        $this->fotografia('Coreto publicado', ['status' => 'publicado', 'visibilidade' => Visibilidade::Publico]);
        $this->fotografia('Coreto rascunho');
        User::factory()->create(['nome' => 'Conta inativa', 'status' => 'inativo']);

        $xpath = $this->dom($this->get(route('admin.fotografias.index'))->assertOk());
        $this->assertBadge($xpath, 'Coreto publicado', 'Publicado', 'bg-success-soft');
        $this->assertBadge($xpath, 'Coreto publicado', 'Público', 'bg-heritage-100');
        $this->assertBadge($xpath, 'Coreto rascunho', 'Rascunho', 'bg-mist-150');
        $this->assertBadge($xpath, 'Coreto rascunho', 'Privado', 'bg-mist-150');

        $xpath = $this->dom($this->get(route('admin.usuarios.index'))->assertOk());
        $this->assertBadge($xpath, 'Conta inativa', 'Inativo', 'bg-mist-150');
        $this->assertSame(1, $xpath->query('//tbody//span[contains(@class, "bg-success-soft")][normalize-space()="Ativo"]')->length);
    }

    public function test_row_actions_keep_methods_confirmations_and_stay_above_row_link(): void
    {
        $this->actingAs($this->admin());
        $categoria = Categoria::create(['titulo' => 'Praças']);
        $fotografia = $this->fotografia('Coreto');
        $excluida = $this->fotografia('Antiga');
        $excluida->delete();

        $xpath = $this->dom($this->get(route('admin.categorias.index'))->assertOk());
        $linha = $xpath->query(sprintf('//tr[@id="categoria-%d"]', $categoria->id))->item(0);
        $this->assertContains('relative', $this->classes($linha));
        $acoes = $xpath->query('.//div[contains(@class, "z-10")]', $linha)->item(0);
        $this->assertSame(route('admin.categorias.edit', $categoria), $xpath->query('.//a[normalize-space()="Editar"]', $acoes)->item(0)?->getAttribute('href'));
        $form = $xpath->query('.//form', $acoes)->item(0);
        $this->assertSame(route('admin.categorias.destroy', $categoria), $form->getAttribute('action'));
        $this->assertSame('DELETE', $xpath->query('.//input[@name="_method"]', $form)->item(0)?->getAttribute('value'));
        $this->assertSame('Excluir categoria', $form->getAttribute('data-confirm-label'));
        $excluir = $xpath->query('.//button', $form)->item(0);
        $this->assertSame('submit', $excluir->getAttribute('type'));
        $this->assertContains('text-danger', $this->classes($excluir));

        // Lixeira: restaurar (PATCH, confirmação primária) e exclusão permanente; linhas sem link.
        $xpath = $this->dom($this->get(route('admin.fotografias.trashed'))->assertOk());
        $linha = $xpath->query(sprintf('//tr[@id="fotografia-excluida-%d"]', $excluida->id))->item(0);
        $restaurar = $xpath->query(sprintf('.//form[@action="%s"]', route('admin.fotografias.restore', $excluida->id)), $linha)->item(0);
        $this->assertSame('PATCH', $xpath->query('.//input[@name="_method"]', $restaurar)->item(0)?->getAttribute('value'));
        $this->assertSame('primary', $restaurar->getAttribute('data-confirm-variant'));
        $this->assertContains('text-institutional', $this->classes($xpath->query('.//button[@type="submit"]', $restaurar)->item(0)));
        $permanente = $xpath->query(sprintf('.//form[@action="%s"]', route('admin.fotografias.force-destroy', $excluida->id)), $linha)->item(0);
        $this->assertSame('DELETE', $xpath->query('.//input[@name="_method"]', $permanente)->item(0)?->getAttribute('value'));
        $this->assertSame(0, $xpath->query('.//a', $linha)->length);
        $this->assertSame(route('admin.fotografias.index'), $xpath->query('//main//header//a[normalize-space()="Voltar para fotografias"]')->item(0)?->getAttribute('href'));

        $xpath = $this->dom($this->get(route('admin.fotografias.index'))->assertOk());
        $this->assertSame(route('admin.fotografias.trashed'), $xpath->query('//main//header//a[normalize-space()="Lixeira"]')->item(0)?->getAttribute('href'));
        $this->assertSame(route('admin.fotografias.create'), $xpath->query('//main//header//a[normalize-space()="Nova fotografia"]')->item(0)?->getAttribute('href'));
        $this->assertSame(1, $xpath->query(sprintf('//tr[@id="fotografia-%d"]//form[@action="%s"]', $fotografia->id, route('admin.fotografias.destroy', $fotografia)))->length);
    }

    public function test_sorting_search_and_pagination_survive_inside_the_table(): void
    {
        $this->actingAs($this->admin());
        foreach (range(1, 20) as $numero) {
            Categoria::create(['titulo' => sprintf('Coreto %02d', $numero)]);
        }

        $xpath = $this->dom($this->get(route('admin.categorias.index', ['q' => 'Coreto', 'ordem' => 'titulo', 'direcao' => 'desc']))->assertOk());
        $tabela = $xpath->query('//main//table[contains(@class, "admin-table")]')->item(0);
        $this->assertSame('descending', $xpath->query('.//th[a[@data-ordem="titulo"]]', $tabela)->item(0)?->getAttribute('aria-sort'));
        $this->assertSame('Coreto 20', trim($xpath->query('.//tbody/tr[1]/td[1]//a', $tabela)->item(0)->textContent));

        // Ordenar por outra coluna mantém a pesquisa e volta à primeira página.
        parse_str(parse_url($xpath->query('.//a[@data-ordem="itens"]', $tabela)->item(0)->getAttribute('href'), PHP_URL_QUERY), $ordenar);
        $this->assertSame('Coreto', $ordenar['q']);
        $this->assertArrayNotHasKey('page', $ordenar);

        // A paginação, no rodapé do x-admin.table, mantém pesquisa e ordenação.
        $proxima = $xpath->query('//main//nav[@aria-label="Paginação"]//a[@rel="next" or @aria-label="Próxima página" or normalize-space()="Próxima"]')->item(0);
        $this->assertInstanceOf(DOMElement::class, $proxima);
        $this->assertSame(0, $xpath->query('//main//table//nav')->length);
        parse_str(parse_url($proxima->getAttribute('href'), PHP_URL_QUERY), $pagina);
        $this->assertSame(['q' => 'Coreto', 'ordem' => 'titulo', 'direcao' => 'desc', 'page' => '2'], $pagina);

        $xpath = $this->dom($this->get(route('admin.categorias.index', $pagina))->assertOk());
        $this->assertSame('Coreto 05', trim($xpath->query('//main//tbody/tr[1]/td[1]//a')->item(0)->textContent));
    }

    public function test_audit_list_distinguishes_no_events_from_filters_without_results(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        Livewire::test(AuditEventList::class)
            ->assertSee('Nenhum evento de auditoria registrado')
            ->assertDontSee('Nenhum evento encontrado');

        $this->dadosDeExemplo($admin);

        // Filtros sem resultado: o estado vazio oferece o mesmo clearFilters do formulário.
        $componente = Livewire::test(AuditEventList::class)
            ->assertSee('Praças')
            ->set('entidade', AuditEntity::User->value)
            ->assertSee('Nenhum evento encontrado')
            ->assertDontSee('Praças');
        $this->assertSame(2, substr_count($componente->html(), 'wire:click="clearFilters"'));

        $componente->call('clearFilters')->assertSee('Praças')->assertDontSee('Nenhum evento encontrado');
    }

    public function test_operator_permissions_on_listings_are_unchanged(): void
    {
        $operador = User::factory()->create(['role' => 'operador', 'status' => 'ativo']);
        $fotografia = $this->fotografia('Coreto');
        $this->actingAs($operador);

        $xpath = $this->dom($this->get(route('admin.fotografias.index'))->assertOk());
        $this->assertSame(0, $xpath->query('//main//a[normalize-space()="Lixeira"]')->length);
        $this->assertSame(1, $xpath->query(sprintf('//form[@action="%s"]', route('admin.fotografias.destroy', $fotografia)))->length);

        foreach (['admin.categorias.index', 'admin.assuntos.index', 'admin.palavras-chave.index', 'admin.autores.index', 'admin.pessoas.index'] as $rota) {
            $this->get(route($rota))->assertOk();
        }
        foreach (['admin.fotografias.trashed', 'admin.usuarios.index', 'admin.auditoria.index', 'admin.downloads.index'] as $rota) {
            $this->get(route($rota))->assertForbidden();
        }
    }

    private function dadosDeExemplo(User $admin): void
    {
        app(AuditRecorder::class)->record(
            AuditContext::forUser($admin, AuditSource::Web, (string) Str::uuid()),
            new AuditEventData(action: AuditAction::Created, subjectType: AuditEntity::Categoria, subjectId: '1', subjectLabel: 'Praças', newValues: ['titulo' => 'Praças']),
        );
        $this->fotografia('Coreto', ['status' => 'publicado', 'visibilidade' => Visibilidade::Publico]);
        $this->fotografia('Antiga')->delete();
        Categoria::create(['titulo' => 'Praças']);
        Assunto::create(['titulo' => 'Urbanização']);
        PalavraChave::create(['termo' => 'coreto']);
        Autor::create(['nome' => 'Foto Estúdio', 'tipo' => 'pessoa']);
        Pessoa::create(['nome' => 'Maria']);
        Contribuicao::create(['nome' => 'Ana', 'email' => 'ana@memorias.test', 'titulo' => 'Fotos da praça', 'autorizacao' => true]);
    }

    private function assertCabecalho(DOMXPath $xpath, string $titulo, string $contexto): void
    {
        $this->assertSame([$titulo], $this->textos($xpath, '//h1'), $contexto);
        $h1 = $xpath->query('//main//header/div/h1')->item(0);
        $this->assertInstanceOf(DOMElement::class, $h1, "{$contexto}: h1 fora do x-admin.page-header");
        $this->assertNotContains('font-serif', $this->classes($h1), $contexto);
        $this->assertContains('text-heritage-700', $this->classes($xpath->query('//main//header/div/p[contains(@class, "font-medium")]')->item(0)), "{$contexto}: eyebrow");
    }

    private function assertBadge(DOMXPath $xpath, string $linha, string $texto, string $classe): void
    {
        $badge = $xpath->query(sprintf('//tbody/tr[.//a[normalize-space()="%s"]]//span[contains(@class, "rounded-full")][normalize-space()="%s"]', $linha, $texto))->item(0);
        $this->assertInstanceOf(DOMElement::class, $badge, "Badge {$texto} em {$linha}");
        $this->assertContains($classe, $this->classes($badge), "{$linha}: {$texto}");
    }

    /** @param  array<string, mixed>  $atributos */
    private function fotografia(string $titulo, array $atributos = []): ItemAcervo
    {
        return ItemAcervo::create([
            'titulo' => $titulo,
            'tipo_item' => 'fotografia',
            'tipo_data' => 'desconhecida',
            'status' => 'rascunho',
            'visibilidade' => Visibilidade::Privado,
            ...$atributos,
        ]);
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
    private function textos(DOMXPath $xpath, string $consulta): array
    {
        $textos = [];
        foreach ($xpath->query($consulta) as $no) {
            $textos[] = trim(preg_replace('/\s+/', ' ', $no->textContent));
        }

        return $textos;
    }
}
