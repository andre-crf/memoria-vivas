<?php

namespace Tests\Feature;

use App\Models\ItemAcervo;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemAcervoAuditoriaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $extra
     */
    private function criarItem(array $extra = []): ItemAcervo
    {
        return ItemAcervo::create([
            'titulo' => 'Praca central',
            'tipo_item' => 'fotografia',
            'tipo_data' => 'ano',
            'ano' => 1980,
            'status' => 'rascunho',
            ...$extra,
        ]);
    }

    public function test_criacao_atribui_o_usuario_autenticado(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $item = $this->criarItem();

        $this->assertSame($user->id, $item->created_by_user_id);
        $this->assertSame($user->id, $item->updated_by_user_id);
        $this->assertNull($item->deleted_by_user_id);
    }

    public function test_edicao_atribui_o_usuario_autenticado(): void
    {
        $autor = User::factory()->create();
        $editor = User::factory()->create();

        $this->actingAs($autor);
        $item = $this->criarItem();

        $this->actingAs($editor);
        $item->update(['titulo' => 'Praca central reformada']);
        $item->refresh();

        $this->assertSame($autor->id, $item->created_by_user_id);
        $this->assertSame($editor->id, $item->updated_by_user_id);
    }

    public function test_exclusao_registra_quem_excluiu(): void
    {
        $autor = User::factory()->create();
        $excluidor = User::factory()->create();

        $this->actingAs($autor);
        $item = $this->criarItem();

        $this->actingAs($excluidor);
        $item->delete();

        $item = ItemAcervo::withTrashed()->findOrFail($item->id);

        $this->assertNotNull($item->deleted_at);
        $this->assertSame($excluidor->id, $item->deleted_by_user_id);
        $this->assertSame($excluidor->id, $item->excluidoPor->id);
        $this->assertSame($autor->id, $item->created_by_user_id);
    }

    public function test_restauracao_limpa_quem_excluiu_e_registra_quem_restaurou(): void
    {
        $autor = User::factory()->create();
        $excluidor = User::factory()->create();
        $restaurador = User::factory()->create();

        $this->actingAs($autor);
        $item = $this->criarItem();

        $this->actingAs($excluidor);
        $item->delete();

        $this->actingAs($restaurador);
        $item->restore();
        $item->refresh();

        $this->assertNull($item->deleted_at);
        $this->assertNull($item->deleted_by_user_id);
        $this->assertNull($item->excluidoPor);
        $this->assertSame($autor->id, $item->created_by_user_id);
        $this->assertSame($restaurador->id, $item->updated_by_user_id);
    }

    public function test_campos_de_auditoria_no_payload_sao_ignorados(): void
    {
        $user = User::factory()->create();
        $intruso = User::factory()->create();

        $this->actingAs($user);

        $item = $this->criarItem([
            'created_by_user_id' => $intruso->id,
            'updated_by_user_id' => $intruso->id,
            'deleted_by_user_id' => $intruso->id,
        ]);

        $this->assertSame($user->id, $item->created_by_user_id);
        $this->assertSame($user->id, $item->updated_by_user_id);
        $this->assertNull($item->deleted_by_user_id);
    }

    public function test_sem_usuario_autenticado_os_campos_ficam_nulos(): void
    {
        $item = $this->criarItem();

        $this->assertNull($item->created_by_user_id);
        $this->assertNull($item->updated_by_user_id);
    }

    public function test_usuario_com_historico_nao_pode_ser_apagado(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->criarItem();

        $this->expectException(QueryException::class);

        $user->delete();
    }
}
