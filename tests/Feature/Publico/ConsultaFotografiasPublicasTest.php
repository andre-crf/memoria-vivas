<?php

namespace Tests\Feature\Publico;

use App\Enums\Visibilidade;
use App\Models\Arquivo;
use App\Models\ItemAcervo;
use App\Queries\Publico\ConsultaFotografiasPublicas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ConsultaFotografiasPublicasTest extends TestCase
{
    use RefreshDatabase;

    private ConsultaFotografiasPublicas $consulta;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consulta = app(ConsultaFotografiasPublicas::class);
    }

    public function test_consulta_inclui_somente_fotografias_publicadas_publicas_com_imagem_elegivel(): void
    {
        $elegivel = $this->fotografia();
        $this->arquivo($elegivel, 'thumbnail');

        $rascunho = $this->fotografia(['status' => 'rascunho']);
        $this->arquivo($rascunho, 'thumbnail');

        $emRevisao = $this->fotografia(['status' => 'em_revisao']);
        $this->arquivo($emRevisao, 'thumbnail');

        $arquivada = $this->fotografia(['status' => 'arquivado']);
        $this->arquivo($arquivada, 'thumbnail');

        $privada = $this->fotografia(['visibilidade' => Visibilidade::Privado]);
        $this->arquivo($privada, 'thumbnail');

        $documento = $this->fotografia(['tipo_item' => 'documento']);
        $this->arquivo($documento, 'thumbnail');

        $excluida = $this->fotografia();
        $this->arquivo($excluida, 'thumbnail');
        $excluida->delete();

        $this->assertSame(
            [$elegivel->id],
            $this->consulta->query()->pluck('id')->all(),
        );
    }

    public function test_consulta_exige_derivacao_de_imagem_e_nao_aceita_original_pdf_ou_metadados_incoerentes(): void
    {
        $semArquivo = $this->fotografia();

        $somenteOriginal = $this->fotografia();
        $this->arquivo($somenteOriginal, 'original');

        $pdf = $this->fotografia();
        $this->arquivo($pdf, 'thumbnail', [
            'mime_type' => 'application/pdf',
            'tipo_arquivo' => 'documento',
        ]);

        $tipoIncoerente = $this->fotografia();
        $this->arquivo($tipoIncoerente, 'thumbnail', [
            'tipo_arquivo' => 'documento',
        ]);

        $mimeIncoerente = $this->fotografia();
        $this->arquivo($mimeIncoerente, 'thumbnail', [
            'mime_type' => 'application/octet-stream',
        ]);

        $this->assertEmpty($this->consulta->query()->get());
        $this->assertNull($this->consulta->porIdentificador($semArquivo->id));
    }

    public function test_consulta_aceita_cada_versao_publica_otimizada(): void
    {
        $fotografias = collect(['thumbnail', 'medium', 'large'])
            ->map(function (string $versao): ItemAcervo {
                $fotografia = $this->fotografia();
                $this->arquivo($fotografia, $versao);

                return $fotografia;
            });

        $this->assertEqualsCanonicalizing(
            $fotografias->pluck('id')->all(),
            $this->consulta->query()->pluck('id')->all(),
        );
    }

    public function test_consulta_carrega_somente_as_derivacoes_elegiveis(): void
    {
        $fotografia = $this->fotografia();
        $thumbnail = $this->arquivo($fotografia, 'thumbnail');
        $medium = $this->arquivo($fotografia, 'medium');
        $this->arquivo($fotografia, 'original');

        $resultado = $this->consulta->porIdentificador($fotografia->id);

        $this->assertNotNull($resultado);
        $this->assertTrue($resultado->relationLoaded('arquivos'));
        $this->assertEqualsCanonicalizing(
            [$thumbnail->id, $medium->id],
            $resultado->arquivos->pluck('id')->all(),
        );
        $this->assertTrue($resultado->arquivos->every(
            fn (Arquivo $arquivo): bool => $arquivo->isImagem() && ! $arquivo->isOriginal(),
        ));
    }

    public function test_consulta_por_identificador_retorna_somente_fotografia_elegivel(): void
    {
        $elegivel = $this->fotografia();
        $this->arquivo($elegivel, 'large');

        $privada = $this->fotografia(['visibilidade' => Visibilidade::Privado]);
        $this->arquivo($privada, 'large');

        $this->assertTrue($elegivel->is($this->consulta->porIdentificador((string) $elegivel->id)));
        $this->assertNull($this->consulta->porIdentificador($privada->id));
        $this->assertNull($this->consulta->porIdentificador(999999));
    }

    public function test_consulta_por_identificador_nao_retorna_fotografia_removida_logicamente(): void
    {
        $fotografia = $this->fotografia();
        $this->arquivo($fotografia, 'thumbnail');
        $fotografia->delete();

        $this->assertNull($this->consulta->porIdentificador($fotografia->id));
    }

    public function test_recentes_ordena_por_criacao_e_id_e_respeita_limite(): void
    {
        $maisAntiga = $this->fotografia();
        $this->arquivo($maisAntiga, 'thumbnail');

        $recenteMenorId = $this->fotografia();
        $this->arquivo($recenteMenorId, 'thumbnail');

        $recenteMaiorId = $this->fotografia();
        $this->arquivo($recenteMaiorId, 'thumbnail');

        DB::table('item_acervos')->where('id', $maisAntiga->id)->update([
            'created_at' => now()->subDay(),
        ]);
        DB::table('item_acervos')->whereIn('id', [$recenteMenorId->id, $recenteMaiorId->id])->update([
            'created_at' => now(),
        ]);

        $resultado = $this->consulta->recentes(2);

        $this->assertSame(
            [$recenteMaiorId->id, $recenteMenorId->id],
            $resultado->pluck('id')->all(),
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function fotografia(array $attributes = []): ItemAcervo
    {
        return ItemAcervo::create([
            'titulo' => 'Fotografia '.uniqid(),
            'tipo_item' => 'fotografia',
            'tipo_data' => 'desconhecida',
            'status' => 'publicado',
            'visibilidade' => Visibilidade::Publico,
            ...$attributes,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function arquivo(ItemAcervo $fotografia, string $versao, array $attributes = []): Arquivo
    {
        return Arquivo::create([
            'item_acervo_id' => $fotografia->id,
            'nome_original' => $versao === 'original' ? 'fotografia.jpg' : null,
            'provider' => 'local',
            'storage_path' => "acervo/{$fotografia->id}/{$versao}.jpg",
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'tipo_arquivo' => 'imagem',
            'versao_arquivo' => $versao,
            'width' => 800,
            'height' => 600,
            ...$attributes,
        ]);
    }
}
