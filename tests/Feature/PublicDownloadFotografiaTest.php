<?php

namespace Tests\Feature;

use App\Models\Arquivo;
use App\Models\ItemAcervo;
use App\Models\MotivoDownload;
use App\Models\PerfilDownload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicDownloadFotografiaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_visitante_preenche_formulario_e_recebe_derivacao_sem_expor_original(): void
    {
        $fotografia = $this->fotografia();
        $original = $this->arquivo($fotografia, 'original', 'original.jpg');
        $large = $this->arquivo($fotografia, 'large', 'large.jpg');
        Storage::disk('local')->put($original->storage_path, 'conteudo original');
        Storage::disk('local')->put($large->storage_path, 'conteudo da derivacao');

        $this->get(route('public.fotografias.show', $fotografia))
            ->assertOk()->assertSee(route('public.fotografias.download.create', $fotografia), false);
        $this->get(route('public.fotografias.download.create', $fotografia))
            ->assertOk()->assertSee('Condições de uso')->assertSee('Confirmar e baixar');

        $response = $this->post(route('public.fotografias.download.store', $fotografia), $this->dados());
        $response->assertOk()->assertDownload("memorias-vivas-{$fotografia->id}.jpg");
        $this->assertSame('conteudo da derivacao', file_get_contents($response->baseResponse->getFile()->getPathname()));
        $this->assertDatabaseHas('registro_downloads', [
            'item_acervo_id' => $fotografia->id,
            'arquivo_id' => $large->id,
            'nome' => 'Ana Silva',
            'email' => 'ana@example.com',
            'finalidade' => 'Pesquisa sobre a história de Umuarama',
        ]);
        $this->assertDatabaseCount('registro_downloads', 1);
    }

    public function test_campos_invalidos_nao_registram_nem_entregam_arquivo(): void
    {
        $fotografia = $this->fotografia();
        $large = $this->arquivo($fotografia, 'large', 'large.jpg');
        Storage::disk('local')->put($large->storage_path, 'imagem');

        $this->post(route('public.fotografias.download.store', $fotografia), [
            ...$this->dados(),
            'email' => 'invalido',
            'aceite' => null,
            'motivo_download_id' => 99999,
        ])->assertSessionHasErrors(['email', 'aceite', 'motivo_download_id']);

        $this->assertDatabaseCount('registro_downloads', 0);
    }

    public function test_fotografia_privada_ou_sem_arquivo_nao_pode_ser_baixada(): void
    {
        $privada = $this->fotografia(['visibilidade' => 'privado']);
        $arquivo = $this->arquivo($privada, 'large', 'private.jpg');
        Storage::disk('local')->put($arquivo->storage_path, 'privado');

        $this->get(route('public.fotografias.download.create', $privada))->assertNotFound();
        $this->post(route('public.fotografias.download.store', $privada), $this->dados())->assertNotFound();

        $publica = $this->fotografia();
        $this->arquivo($publica, 'large', 'ausente.jpg');
        $this->post(route('public.fotografias.download.store', $publica), $this->dados())->assertNotFound();
        $this->assertDatabaseCount('registro_downloads', 0);
    }

    private function fotografia(array $dados = []): ItemAcervo
    {
        return ItemAcervo::create(array_merge([
            'titulo' => 'Praça central',
            'tipo_item' => 'fotografia',
            'status' => 'publicado',
            'visibilidade' => 'publico',
        ], $dados));
    }

    private function arquivo(ItemAcervo $fotografia, string $versao, string $nome): Arquivo
    {
        return $fotografia->arquivos()->create([
            'provider' => 'local',
            'storage_path' => "acervo/{$fotografia->id}/{$nome}",
            'nome_original' => $versao === 'original' ? $nome : null,
            'mime_type' => 'image/jpeg',
            'file_size' => 100,
            'tipo_arquivo' => 'imagem',
            'versao_arquivo' => $versao,
        ]);
    }

    private function dados(): array
    {
        return [
            'nome' => 'Ana Silva',
            'email' => 'ana@example.com',
            'perfil_download_id' => PerfilDownload::query()->where('titulo', 'Pesquisador(a)')->value('id'),
            'motivo_download_id' => MotivoDownload::query()->where('titulo', 'Pesquisa acadêmica')->value('id'),
            'finalidade' => 'Pesquisa sobre a história de Umuarama',
            'aceite' => '1',
        ];
    }
}
