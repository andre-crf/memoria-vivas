<?php

namespace Tests\Feature;

use App\Models\ItemAcervo;
use App\Models\MotivoDownload;
use App\Models\PerfilDownload;
use App\Models\RegistroDownload;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class RelatorioDownloadsAbasTest extends TestCase
{
    use RefreshDatabase;

    public function test_statistics_is_default_tab_and_skips_record_listing(): void
    {
        $admin = $this->admin();
        $this->registros(2);

        $resposta = $this->actingAs($admin)->get(route('admin.downloads.index'))->assertOk();
        $this->assertSame('estatisticas', $resposta->viewData('aba'));
        $this->assertSame(2, $resposta->viewData('total'));
        $this->assertFalse(isset($resposta->original->getData()['registros']), 'A aba de estatísticas não pagina registros.');

        $xpath = $this->dom($resposta);
        $abas = $xpath->query('//nav[@aria-label="Seções do relatório"]//a');
        $this->assertSame(2, $abas->length);
        $this->assertSame('page', $abas->item(0)->getAttribute('aria-current'));
        $this->assertFalse($abas->item(1)->hasAttribute('aria-current'));
        $this->assertSame(route('admin.downloads.index'), $abas->item(0)->getAttribute('href'));
        $this->assertSame(route('admin.downloads.index', ['aba' => 'registros']), $abas->item(1)->getAttribute('href'));
        $this->assertSame(0, $xpath->query('//table[contains(@class, "min-w-[65rem]")]')->length);
        $this->assertSame(0, $xpath->query('//form//input[@name="aba"]')->length);
    }

    public function test_records_tab_lists_paginated_records_without_aggregations(): void
    {
        $admin = $this->admin();
        $this->registros(21);

        $resposta = $this->actingAs($admin)->get(route('admin.downloads.index', ['aba' => 'registros', 'q' => 'Pessoa']))->assertOk();
        $this->assertSame('registros', $resposta->viewData('aba'));
        $this->assertSame(21, $resposta->viewData('total'));
        $this->assertSame(20, $resposta->viewData('registros')->count());
        foreach (['serie', 'perfisAgrupados', 'paisesAgrupados'] as $estatistica) {
            $this->assertFalse(isset($resposta->original->getData()[$estatistica]), "A aba de registros não calcula {$estatistica}.");
        }

        $xpath = $this->dom($resposta);
        $this->assertSame('page', $xpath->query('//nav[@aria-label="Seções do relatório"]//a[normalize-space()="Registros"]')->item(0)->getAttribute('aria-current'));
        // As abas levam os filtros aplicados; o formulário mantém a aba aberta.
        $this->assertSame(route('admin.downloads.index', ['q' => 'Pessoa']), $xpath->query('//nav[@aria-label="Seções do relatório"]//a[normalize-space()="Estatísticas"]')->item(0)->getAttribute('href'));
        $this->assertSame('registros', $xpath->query('//form[@method="GET"]//input[@type="hidden"][@name="aba"]')->item(0)?->getAttribute('value'));
        // A paginação fica no rodapé da tabela e preserva aba e busca.
        $proxima = $xpath->query('//a[contains(@href, "page=2")]')->item(0)?->getAttribute('href');
        $this->assertNotNull($proxima);
        $this->assertStringContainsString('aba=registros', $proxima);
        $this->assertStringContainsString('q=Pessoa', $proxima);
        $this->assertSame(route('admin.downloads.export', ['q' => 'Pessoa']), $xpath->query('//a[normalize-space()="Exportar CSV"]')->item(0)?->getAttribute('href'));
    }

    public function test_unknown_tab_falls_back_to_statistics(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.downloads.index', ['aba' => 'inexistente']))
            ->assertOk()
            ->assertViewHas('aba', 'estatisticas');
    }

    public function test_operator_cannot_open_any_tab(): void
    {
        $operador = User::factory()->create(['role' => 'operador', 'status' => 'ativo']);

        $this->actingAs($operador)->get(route('admin.downloads.index', ['aba' => 'registros']))->assertForbidden();
        $this->actingAs($operador)->get(route('admin.downloads.index', ['aba' => 'estatisticas']))->assertForbidden();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'ativo']);
    }

    private function registros(int $quantidade): void
    {
        $foto = ItemAcervo::create(['titulo' => 'Praça central', 'tipo_item' => 'fotografia']);

        for ($i = 1; $i <= $quantidade; $i++) {
            RegistroDownload::create([
                'item_acervo_id' => $foto->id,
                'motivo_download_id' => MotivoDownload::query()->value('id'),
                'perfil_download_id' => PerfilDownload::query()->value('id'),
                'nome' => "Pessoa {$i}",
                'email' => "pessoa{$i}@example.test",
                'finalidade' => 'Pesquisa sobre a história local',
                'termos_aceitos_em' => now(),
                'created_at' => now()->subMinutes($i),
            ]);
        }
    }

    private function dom(TestResponse $resposta): DOMXPath
    {
        $dom = new DOMDocument;
        @$dom->loadHTML('<?xml encoding="utf-8"?>'.$resposta->getContent(), LIBXML_NOERROR);

        return new DOMXPath($dom);
    }
}
