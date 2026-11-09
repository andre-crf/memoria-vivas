<?php

namespace Tests\Feature;

use App\Models\ItemAcervo;
use App\Models\MotivoDownload;
use App\Models\PerfilDownload;
use App\Models\RegistroDownload;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PainelResumoDownloadsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_admin_sees_last_30_days_summary_with_report_link(): void
    {
        Carbon::setTestNow('2026-11-09 15:00:00');
        $praca = ItemAcervo::create(['titulo' => 'Praça central', 'tipo_item' => 'fotografia']);
        $avenida = ItemAcervo::create(['titulo' => 'Avenida Paraná', 'tipo_item' => 'fotografia']);
        [$pesquisador, $estudante] = PerfilDownload::query()->orderBy('id')->limit(2)->get();

        // Fora do período: conta só para o "último download" se for o mais recente (não é).
        $this->registro($avenida, $estudante, '2026-09-01 12:00:00');
        $this->registro($avenida, $estudante, '2026-09-02 12:00:00');
        $this->registro($avenida, $estudante, '2026-09-03 12:00:00');
        // Dentro dos últimos 30 dias (desde 11/10 em Umuarama).
        $this->registro($praca, $pesquisador, '2026-10-11 03:30:00');
        $this->registro($praca, $pesquisador, '2026-11-08 18:00:00');
        $this->registro($avenida, $estudante, '2026-11-09 13:45:00');

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'ativo']);
        $resposta = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $resumo = $resposta->viewData('resumoDownloads');

        $this->assertSame(3, $resumo['total']);
        $this->assertSame('2026-10-11', $resumo['desde']);
        $this->assertSame('2026-11-09 13:45:00', $resumo['ultimo']->format('Y-m-d H:i:s'));
        $this->assertSame($pesquisador->titulo, $resumo['perfil']->rotulo);
        $this->assertSame(2, $resumo['perfil']->total);
        $this->assertSame('Praça central', $resumo['fotografia']->rotulo);

        $resposta
            ->assertSee('Últimos 30 dias')
            ->assertSee('Perfil mais frequente')
            ->assertSee('Fotografia mais baixada')
            ->assertSee(route('admin.fotografias.show', $praca), false)
            ->assertSee(route('admin.downloads.index'), false);
    }

    public function test_operator_does_not_see_or_query_download_summary(): void
    {
        $this->registro(ItemAcervo::create(['titulo' => 'Praça central', 'tipo_item' => 'fotografia']), PerfilDownload::query()->first(), now());
        $operador = User::factory()->create(['role' => 'operador', 'status' => 'ativo']);

        DB::enableQueryLog();
        $resposta = $this->actingAs($operador)->get(route('admin.dashboard'))->assertOk();
        $consultas = collect(DB::getQueryLog())->pluck('query')->filter(fn (string $sql): bool => str_contains($sql, 'registro_downloads'));

        $this->assertNull($resposta->viewData('resumoDownloads'));
        $this->assertCount(0, $consultas);
        $resposta->assertDontSee('Ver relatório')->assertDontSee(route('admin.downloads.index'), false);
    }

    private function registro(ItemAcervo $foto, PerfilDownload $perfil, mixed $data): void
    {
        RegistroDownload::create([
            'item_acervo_id' => $foto->id,
            'perfil_download_id' => $perfil->id,
            'motivo_download_id' => MotivoDownload::query()->value('id'),
            'nome' => 'Ana Silva',
            'email' => 'ana@example.test',
            'finalidade' => 'Pesquisa',
            'termos_aceitos_em' => $data,
            'created_at' => $data,
        ]);
    }
}
