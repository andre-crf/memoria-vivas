<?php

namespace Tests\Feature;

use App\Auditing\Enums\AuditAction;
use App\Auditing\Enums\AuditEntity;
use App\Enums\Visibilidade;
use App\Models\Arquivo;
use App\Models\AuditEvent;
use App\Models\ItemAcervo;
use App\Models\MotivoDownload;
use App\Models\PerfilDownload;
use App\Models\RegistroDownload;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * O histórico de downloads não depende das derivações nem do item continuarem existindo.
 */
class HistoricoDownloadsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_download_is_recorded_without_country_and_with_snapshots(): void
    {
        $fotografia = $this->publishedPhotograph($this->admin());
        $large = $this->derivative($fotografia, 'large');

        $this->download($fotografia)->assertOk()->assertDownload();

        $registro = RegistroDownload::query()->sole();
        $this->assertNull($registro->pais_id);
        $this->assertNull($registro->estado_id);
        $this->assertNull($registro->municipio_id);
        $this->assertSame($large->id, $registro->arquivo_id);
        $this->assertSame('large', $registro->versao_arquivo);
        $this->assertSame('Praça central', $registro->titulo_item);
        $this->assertSame('MV-0001', $registro->codigo_referencia_item);
        $this->assertDatabaseCount('paises', 0);
    }

    public function test_delivered_version_survives_sync_regeneration_and_replacement(): void
    {
        $admin = $this->admin();
        $fotografia = $this->publishedPhotograph($admin);
        $this->download($fotografia)->assertOk();
        $registro = RegistroDownload::query()->sole();

        $this->actingAs($admin)->post(route('admin.fotografias.regenerate-versions', $fotografia))->assertSessionHas('success');

        $registro->refresh();
        $this->assertNull($registro->arquivo_id);
        $this->assertSame('large', $registro->versao_arquivo);
        $this->assertSame('Grande', $registro->rotuloVersaoEntregue());

        $this->download($fotografia)->assertOk();
        $this->actingAs($admin)
            ->put(route('admin.fotografias.replace-original', $fotografia), ['arquivo_original' => UploadedFile::fake()->image('retrato.jpg', 1200, 1800)])
            ->assertSessionHas('success');

        $this->assertSame(['large', 'large'], RegistroDownload::query()->orderBy('id')->pluck('versao_arquivo')->all());
        $this->assertSame(0, RegistroDownload::query()->whereNotNull('arquivo_id')->count());

        $this->actingAs($admin)->get(route('admin.downloads.index'))
            ->assertOk()
            ->assertSee('Grande')
            ->assertDontSee('Não informado</td>', false);
    }

    public function test_delivered_version_survives_async_replacement(): void
    {
        $admin = $this->admin();
        $fotografia = $this->publishedPhotograph($admin);
        $this->download($fotografia)->assertOk();

        config(['acervo.optimized_processing.async' => true, 'queue.default' => 'database']);
        $this->actingAs($admin)
            ->put(route('admin.fotografias.replace-original', $fotografia), ['arquivo_original' => UploadedFile::fake()->image('nova.jpg', 1800, 1200)])
            ->assertSessionHas('success');
        Artisan::call('queue:work', ['connection' => 'database', '--queue' => 'imagens', '--stop-when-empty' => true, '--sleep' => 0]);

        $this->assertSame(0, DB::table('failed_jobs')->count());
        $this->assertNotNull($this->derivative($fotografia, 'large'));
        $registro = RegistroDownload::query()->sole();
        $this->assertNull($registro->arquivo_id);
        $this->assertSame('large', $registro->versao_arquivo);
        $this->assertSame($fotografia->id, $registro->item_acervo_id);
    }

    public function test_downloads_survive_force_delete_and_audit_records_them(): void
    {
        $admin = $this->admin();
        $fotografia = $this->publishedPhotograph($admin);
        $this->download($fotografia)->assertOk();

        $this->actingAs($admin)->delete(route('admin.fotografias.destroy', $fotografia));
        $this->actingAs($admin)->delete(route('admin.fotografias.force-destroy', $fotografia->id))
            ->assertRedirect(route('admin.fotografias.trashed'));

        $this->assertDatabaseMissing('item_acervos', ['id' => $fotografia->id]);
        $this->assertSame(0, Arquivo::query()->count());
        $this->assertSame([], Storage::disk('local')->allFiles('acervo'));

        $registro = RegistroDownload::query()->sole();
        $this->assertNull($registro->item_acervo_id);
        $this->assertNull($registro->arquivo_id);
        $this->assertTrue($registro->fotografiaExcluida());
        $this->assertSame('Praça central', $registro->tituloFotografia());
        $this->assertSame('Grande', $registro->rotuloVersaoEntregue());
        $this->assertSame('Ana Silva', $registro->nome);

        $evento = AuditEvent::query()
            ->where('subject_type', AuditEntity::ItemAcervo)
            ->where('action', AuditAction::ForceDeleted)
            ->sole();
        $this->assertSame(1, $evento->metadata['cascade']['registro_downloads_count']);
    }

    public function test_report_and_csv_present_downloads_of_removed_photographs(): void
    {
        $admin = $this->admin();
        $removida = $this->publishedPhotograph($admin);
        $this->download($removida)->assertOk();
        $this->actingAs($admin)->delete(route('admin.fotografias.destroy', $removida));
        $this->actingAs($admin)->delete(route('admin.fotografias.force-destroy', $removida->id));

        $mantida = $this->publishedPhotograph($admin, ['titulo' => 'Avenida Paraná', 'codigo_referencia' => 'MV-0002']);
        $this->download($mantida)->assertOk();

        $resposta = $this->actingAs($admin)->get(route('admin.downloads.index'));
        $resposta->assertOk()
            ->assertSee('Praça central')
            ->assertSee('MV-0001')
            ->assertSee('Excluída definitivamente')
            ->assertSee('Fotografias excluídas')
            ->assertSee(route('admin.fotografias.show', $mantida), false)
            ->assertDontSee(route('admin.fotografias.show', $removida->id), false);
        $this->assertSame(2, $resposta->viewData('total'));

        // A busca pelo título ainda encontra o registro da fotografia excluída.
        $busca = $this->actingAs($admin)->get(route('admin.downloads.index', ['q' => 'Praça']));
        $this->assertSame(1, $busca->viewData('total'));

        $csv = substr($this->csv($admin), 3); // sem o BOM UTF-8
        $linhas = array_map(fn (string $linha) => str_getcsv($linha, ';', '"', ''), array_filter(explode("\n", trim($csv))));
        $this->assertSame(['Data (Umuarama)', 'Fotografia', 'Registro', 'Código de referência', 'Situação', 'Solicitante', 'E-mail', 'Perfil', 'Motivo', 'Finalidade', 'Versão'], $linhas[0]);
        $porTitulo = collect(array_slice($linhas, 1))->keyBy(1);
        $this->assertSame(['', 'MV-0001', 'Excluída definitivamente', 'Grande'], [$porTitulo['Praça central'][2], $porTitulo['Praça central'][3], $porTitulo['Praça central'][4], $porTitulo['Praça central'][10]]);
        $this->assertSame([(string) $mantida->id, 'MV-0002', 'No acervo', 'Grande'], [$porTitulo['Avenida Paraná'][2], $porTitulo['Avenida Paraná'][3], $porTitulo['Avenida Paraná'][4], $porTitulo['Avenida Paraná'][10]]);
    }

    public function test_report_permissions_are_unchanged_for_removed_records(): void
    {
        $admin = $this->admin();
        $fotografia = $this->publishedPhotograph($admin);
        $this->download($fotografia)->assertOk();
        $this->actingAs($admin)->delete(route('admin.fotografias.destroy', $fotografia));
        $this->actingAs($admin)->delete(route('admin.fotografias.force-destroy', $fotografia->id));

        $operador = User::factory()->create(['role' => 'operador', 'status' => 'ativo']);
        $this->actingAs($operador)->get(route('admin.downloads.index'))->assertForbidden();
        $this->actingAs($operador)->get(route('admin.downloads.export'))->assertForbidden();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'ativo']);
    }

    private function publishedPhotograph(User $admin, array $override = []): ItemAcervo
    {
        $this->actingAs($admin)->post(route('admin.fotografias.store'), $override + [
            'titulo' => 'Praça central',
            'codigo_referencia' => 'MV-0001',
            'tipo_item' => 'fotografia',
            'tipo_data' => 'ano',
            'ano' => 1980,
            'estado_conservacao' => 'bom',
            'status' => 'publicado',
            'visibilidade' => Visibilidade::Publico->value,
            'arquivo_original' => UploadedFile::fake()->image('original.jpg', 2400, 1600),
        ])->assertSessionHasNoErrors();
        auth()->logout();

        return ItemAcervo::query()->latest('id')->firstOrFail();
    }

    private function derivative(ItemAcervo $fotografia, string $versao): ?Arquivo
    {
        return Arquivo::query()->where('item_acervo_id', $fotografia->id)->where('versao_arquivo', $versao)->first();
    }

    private function download(ItemAcervo $fotografia)
    {
        auth()->logout();

        return $this->post(route('public.fotografias.download.store', $fotografia), [
            'nome' => 'Ana Silva',
            'email' => 'ana@example.com',
            'perfil_download_id' => PerfilDownload::query()->where('titulo', 'Pesquisador(a)')->value('id'),
            'motivo_download_id' => MotivoDownload::query()->where('titulo', 'Pesquisa acadêmica')->value('id'),
            'finalidade' => 'Pesquisa sobre a história de Umuarama',
            'aceite' => '1',
        ]);
    }

    private function csv(User $admin): string
    {
        $resposta = $this->actingAs($admin)->get(route('admin.downloads.export'))->assertOk();
        ob_start();
        $resposta->baseResponse->sendContent();

        return (string) ob_get_clean();
    }
}
