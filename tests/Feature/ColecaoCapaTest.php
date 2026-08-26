<?php

namespace Tests\Feature;

use App\Models\Colecao;
use App\Models\ItemAcervo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ColecaoCapaTest extends TestCase
{
    use RefreshDatabase;

    private function criarItem(string $titulo = 'Praca central'): ItemAcervo
    {
        return ItemAcervo::create([
            'titulo' => $titulo,
            'tipo_item' => 'fotografia',
            'tipo_data' => 'desconhecida',
            'status' => 'rascunho',
        ]);
    }

    public function test_colecao_pode_ficar_sem_capa(): void
    {
        $colecao = Colecao::create(['titulo' => 'Umuarama nos anos 80']);

        $this->assertNull($colecao->item_capa_id);
        $this->assertNull($colecao->itemCapa);
    }

    public function test_colecao_pode_ter_um_item_como_capa(): void
    {
        $item = $this->criarItem();

        $colecao = Colecao::create([
            'titulo' => 'Umuarama nos anos 80',
            'item_capa_id' => $item->id,
        ]);

        $colecao->refresh();

        $this->assertSame($item->id, $colecao->itemCapa->id);
        $this->assertSame('Praca central', $colecao->itemCapa->titulo);
    }

    public function test_capa_nao_precisa_ser_um_item_da_propria_colecao(): void
    {
        $capa = $this->criarItem('Foto de capa');
        $membro = $this->criarItem('Foto interna');

        $colecao = Colecao::create(['titulo' => 'Umuarama nos anos 80', 'item_capa_id' => $capa->id]);
        $colecao->itensAcervo()->attach($membro);

        $this->assertSame($capa->id, $colecao->itemCapa->id);
        $this->assertCount(1, $colecao->itensAcervo()->get());
    }

    public function test_exclusao_definitiva_do_item_apenas_limpa_a_capa(): void
    {
        $item = $this->criarItem();
        $colecao = Colecao::create(['titulo' => 'Umuarama nos anos 80', 'item_capa_id' => $item->id]);

        $item->forceDelete();
        $colecao->refresh();

        $this->assertTrue($colecao->exists);
        $this->assertNull($colecao->item_capa_id);
    }

    public function test_coluna_textual_de_imagem_foi_removida(): void
    {
        $this->assertFalse(Schema::hasColumn('colecoes', 'imagem_capa'));
        $this->assertTrue(Schema::hasColumn('colecoes', 'item_capa_id'));
    }
}
