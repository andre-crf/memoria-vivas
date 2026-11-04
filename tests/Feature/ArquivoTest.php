<?php

namespace Tests\Feature;

use App\Models\Arquivo;
use App\Models\ItemAcervo;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArquivoTest extends TestCase
{
    use RefreshDatabase;

    private function criarItem(): ItemAcervo
    {
        return ItemAcervo::create([
            'titulo' => 'Praca central',
            'tipo_item' => 'fotografia',
            'tipo_data' => 'desconhecida',
            'status' => 'rascunho',
        ]);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function criarArquivo(array $extra = []): Arquivo
    {
        return Arquivo::create([
            'nome_original' => 'praca.jpg',
            'provider' => 'local',
            'storage_path' => 'acervo/praca.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'tipo_arquivo' => 'imagem',
            'versao_arquivo' => 'original',
            ...$extra,
        ]);
    }

    public function test_arquivo_exige_item_acervo(): void
    {
        $this->expectException(QueryException::class);

        $this->criarArquivo();
    }

    public function test_derivacoes_aceitam_nome_original_nulo(): void
    {
        $item = $this->criarItem();

        $this->criarArquivo([
            'item_acervo_id' => $item->id,
            'versao_arquivo' => 'original',
        ]);

        foreach (['thumbnail', 'medium', 'large'] as $versao) {
            $derivacao = $this->criarArquivo([
                'item_acervo_id' => $item->id,
                'versao_arquivo' => $versao,
                'nome_original' => null,
                'storage_path' => "acervo/praca-{$versao}.jpg",
            ]);

            $this->assertNull($derivacao->nome_original);
        }

        $this->assertCount(4, $item->arquivos()->get());
    }

    public function test_sha256_e_persistido_e_consultavel(): void
    {
        $item = $this->criarItem();
        $hash = hash('sha256', 'conteudo-de-teste');

        $this->criarArquivo(['item_acervo_id' => $item->id, 'sha256' => $hash]);

        $encontrado = Arquivo::where('sha256', $hash)->firstOrFail();

        $this->assertSame($hash, $encontrado->sha256);
        $this->assertSame(64, strlen($encontrado->sha256));
    }

    public function test_sha256_repetido_e_aceito_para_detectar_duplicatas(): void
    {
        $hash = hash('sha256', 'mesmo-conteudo');

        $this->criarArquivo(['item_acervo_id' => $this->criarItem()->id, 'sha256' => $hash]);
        $this->criarArquivo(['item_acervo_id' => $this->criarItem()->id, 'sha256' => $hash]);

        $this->assertSame(2, Arquivo::where('sha256', $hash)->count());
    }

    public function test_soft_delete_do_item_preserva_os_arquivos(): void
    {
        $item = $this->criarItem();
        $this->criarArquivo(['item_acervo_id' => $item->id]);

        $item->delete();

        $this->assertSame(1, Arquivo::where('item_acervo_id', $item->id)->count());
    }

    public function test_force_delete_do_item_remove_os_arquivos(): void
    {
        $item = $this->criarItem();
        $this->criarArquivo(['item_acervo_id' => $item->id]);

        $item->forceDelete();

        $this->assertSame(0, Arquivo::where('item_acervo_id', $item->id)->count());
    }
}
