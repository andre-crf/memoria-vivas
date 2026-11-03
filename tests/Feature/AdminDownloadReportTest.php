<?php

namespace Tests\Feature;

use App\Models\ItemAcervo;
use App\Models\MotivoDownload;
use App\Models\Pais;
use App\Models\PerfilDownload;
use App\Models\RegistroDownload;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDownloadReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_view_download_report(): void
    {
        $this->get(route('admin.downloads.index'))->assertRedirect(route('login'));

        $operador = User::factory()->create(['role' => 'operador', 'status' => 'ativo']);
        $this->actingAs($operador)->get(route('admin.downloads.index'))->assertForbidden();
        $this->actingAs($operador)->get(route('admin.dashboard'))->assertOk()->assertDontSee('Relatório de downloads');

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'ativo']);
        $this->actingAs($admin)->get(route('admin.downloads.index'))
            ->assertOk()
            ->assertSee('Relatório de downloads')
            ->assertSee('Nenhum download encontrado ainda');
    }

    public function test_report_lists_requests_and_filters_by_photo_and_local_date(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'ativo']);
        $foto = ItemAcervo::create(['titulo' => 'Praça central', 'tipo_item' => 'fotografia']);
        $outra = ItemAcervo::create(['titulo' => 'Avenida Paraná', 'tipo_item' => 'fotografia']);

        $this->registro($foto, 'Ana Silva', 'ana@example.test', '2026-11-03 02:30:00'); // 02/11 23:30 em Umuarama
        $this->registro($foto, 'Bruno Costa', 'bruno@example.test', '2026-11-03 14:00:00');
        $this->registro($outra, 'Carla Souza', 'carla@example.test', '2026-11-03 15:00:00');

        $resposta = $this->actingAs($admin)
            ->get(route('admin.downloads.index', ['q' => 'Praça', 'de' => '2026-11-03', 'ate' => '2026-11-03']));
        $resposta
            ->assertOk()
            ->assertSee('Downloads ao longo do tempo')
            ->assertSee('Motivos da consulta')
            ->assertSee('Mais baixadas')
            ->assertSee('Exportar CSV')
            ->assertSee('Praça central')
            ->assertSee('Bruno Costa')
            ->assertSee('bruno@example.test')
            ->assertSee('Pesquisa sobre a história local')
            ->assertDontSee('Ana Silva')
            ->assertDontSee('Carla Souza');
        $this->assertSame(1, $resposta->viewData('total'));
        $this->assertSame(1, $resposta->viewData('fotografiasDistintas'));
        $this->assertSame('2026-11-03 14:00:00', $resposta->viewData('ultimoDownload')->format('Y-m-d H:i:s'));
        $this->assertSame(1, array_sum(array_column($resposta->viewData('serie'), 'total')));
        $this->assertSame(1, $resposta->viewData('motivosAgrupados')->first()->total);
        $this->assertSame($foto->id, $resposta->viewData('fotografiasAgrupadas')->first()->item_acervo_id);

        $this->actingAs($admin)
            ->get(route('admin.downloads.index', ['q' => '#'.$outra->id]))
            ->assertOk()
            ->assertSee('Carla Souza')
            ->assertDontSee('Bruno Costa');
    }

    public function test_csv_export_is_admin_only_filtered_and_escapes_spreadsheet_formulas(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'ativo']);
        $operador = User::factory()->create(['role' => 'operador', 'status' => 'ativo']);
        $foto = ItemAcervo::create(['titulo' => 'Praça central', 'tipo_item' => 'fotografia']);
        $outra = ItemAcervo::create(['titulo' => 'Avenida Paraná', 'tipo_item' => 'fotografia']);
        $this->registro($foto, '=HYPERLINK("https://example.test")', 'ana@example.test', '2026-11-03 14:00:00');
        $this->registro($outra, 'Carla Souza', 'carla@example.test', '2026-11-03 15:00:00');

        $url = route('admin.downloads.export', ['q' => 'Praça', 'de' => '2026-11-03', 'ate' => '2026-11-03']);
        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs($operador)->get($url)->assertForbidden();

        $resposta = $this->actingAs($admin)->get($url)->assertOk()->assertDownload();
        ob_start();
        $resposta->baseResponse->sendContent();
        $csv = ob_get_clean();

        $this->assertStringContainsString('Praça central', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringNotContainsString('Carla Souza', $csv);
        $this->assertStringContainsString('03/11/2026 11:00', $csv);
    }

    public function test_percentages_use_all_matching_downloads_and_include_missing_profiles(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'ativo']);
        $foto = ItemAcervo::create(['titulo' => 'Praça central', 'tipo_item' => 'fotografia']);
        $outra = ItemAcervo::create(['titulo' => 'Avenida Paraná', 'tipo_item' => 'fotografia']);
        $educativo = MotivoDownload::query()->where('titulo', 'Atividade educativa')->value('id');
        $estudante = PerfilDownload::query()->where('titulo', 'Estudante / aluno(a)')->value('id');

        $this->registro($foto, 'Ana', 'ana@example.test', '2026-11-03 14:00:00');
        $this->registro($foto, 'Bruno', 'bruno@example.test', '2026-11-03 14:01:00')
            ->update(['perfil_download_id' => $estudante, 'motivo_download_id' => $educativo]);
        $this->registro($foto, 'Carla', 'carla@example.test', '2026-11-03 14:02:00')
            ->update(['perfil_download_id' => null, 'motivo_download_id' => $educativo]);
        $this->registro($outra, 'Daniel', 'daniel@example.test', '2026-11-03 14:03:00');

        $resposta = $this->actingAs($admin)->get(route('admin.downloads.index', ['q' => 'Praça']));
        $resposta->assertOk()->assertSee('Perfis dos solicitantes')->assertSee('Não informado')->assertSee('33,3%')->assertSee('66,7%')->assertSee('100,0%');
        $this->assertSame(3, $resposta->viewData('total'));
        $this->assertSame(3, $resposta->viewData('perfisAgrupados')->sum('total'));
        $this->assertSame(3, $resposta->viewData('motivosAgrupados')->sum('total'));
        $this->assertSame(1, $resposta->viewData('fotografiasAgrupadas')->count());
        $this->assertSame(3, $resposta->viewData('fotografiasAgrupadas')->first()->total);
    }

    public function test_invalid_date_range_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'ativo']);

        $this->actingAs($admin)
            ->get(route('admin.downloads.index', ['de' => '2026-11-04', 'ate' => '2026-11-03']))
            ->assertSessionHasErrors('ate');
    }

    private function registro(ItemAcervo $fotografia, string $nome, string $email, string $data): RegistroDownload
    {
        return RegistroDownload::create([
            'item_acervo_id' => $fotografia->id,
            'motivo_download_id' => MotivoDownload::query()->where('titulo', 'Pesquisa acadêmica')->value('id'),
            'perfil_download_id' => PerfilDownload::query()->where('titulo', 'Pesquisador(a)')->value('id'),
            'pais_id' => Pais::query()->where('codigo', 'ZZ')->value('id'),
            'nome' => $nome,
            'email' => $email,
            'finalidade' => 'Pesquisa sobre a história local',
            'termos_aceitos_em' => $data,
            'created_at' => $data,
        ]);
    }
}
