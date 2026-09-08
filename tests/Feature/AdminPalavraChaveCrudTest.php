<?php

namespace Tests\Feature;

use App\Models\ItemAcervo;
use App\Models\PalavraChave;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPalavraChaveCrudTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioInterno(string $role = 'admin'): User
    {
        return User::factory()->create([
            'role' => $role,
            'status' => 'ativo',
        ]);
    }

    private function palavraChave(string $termo = 'centro histórico'): PalavraChave
    {
        return PalavraChave::create(['termo' => $termo]);
    }

    private function item(string $titulo = 'Fotografia da praça'): ItemAcervo
    {
        return ItemAcervo::create([
            'tipo_item' => 'fotografia',
            'titulo' => $titulo,
            'tipo_data' => 'ano',
            'ano' => 1980,
            'estado_conservacao' => 'desconhecido',
            'status' => 'rascunho',
            'visibilidade' => 'privado',
        ]);
    }

    public function test_guest_is_redirected_from_keyword_routes(): void
    {
        $palavraChave = $this->palavraChave();

        $this->get(route('admin.palavras-chave.index'))->assertRedirect('/login');
        $this->get(route('admin.palavras-chave.create'))->assertRedirect('/login');
        $this->post(route('admin.palavras-chave.store'))->assertRedirect('/login');
        $this->get(route('admin.palavras-chave.edit', $palavraChave))->assertRedirect('/login');
        $this->put(route('admin.palavras-chave.update', $palavraChave))->assertRedirect('/login');
        $this->delete(route('admin.palavras-chave.destroy', $palavraChave))->assertRedirect('/login');

        $this->assertDatabaseHas('palavras_chave', ['id' => $palavraChave->id]);
    }

    public function test_admin_and_operator_can_access_keyword_listing_and_forms(): void
    {
        $palavraChave = $this->palavraChave();

        foreach (['admin', 'operador'] as $role) {
            $usuario = $this->usuarioInterno($role);

            $this->actingAs($usuario)
                ->get(route('admin.palavras-chave.index'))
                ->assertOk()
                ->assertSee('Palavras-chave')
                ->assertSee('Nova palavra-chave');

            $this->actingAs($usuario)
                ->get(route('admin.palavras-chave.create'))
                ->assertOk()
                ->assertSee('Cadastrar palavra-chave');

            $this->actingAs($usuario)
                ->get(route('admin.palavras-chave.edit', $palavraChave))
                ->assertOk()
                ->assertSee('Editar palavra-chave')
                ->assertSee($palavraChave->termo)
                ->assertSee('Salvar alterações');
        }
    }

    public function test_listing_displays_keywords_in_term_order(): void
    {
        $this->palavraChave('memória');
        $this->palavraChave('arquitetura');

        $this->actingAs($this->usuarioInterno())
            ->get(route('admin.palavras-chave.index'))
            ->assertOk()
            ->assertSeeInOrder(['arquitetura', 'memória']);
    }

    public function test_listing_displays_empty_state(): void
    {
        $this->actingAs($this->usuarioInterno())
            ->get(route('admin.palavras-chave.index'))
            ->assertOk()
            ->assertSee('Nenhuma palavra-chave cadastrada');
    }

    public function test_listing_displays_associated_item_count_and_delete_confirmation(): void
    {
        $palavraChave = $this->palavraChave();
        $palavraChave->itensAcervo()->attach([
            $this->item('Fotografia 1')->id,
            $this->item('Fotografia 2')->id,
        ]);

        $this->actingAs($this->usuarioInterno())
            ->get(route('admin.palavras-chave.index'))
            ->assertOk()
            ->assertSee($palavraChave->termo)
            ->assertSee('2')
            ->assertSee('Tem certeza que deseja excluir esta palavra-chave?')
            ->assertSee('As associações com os itens do acervo serão removidas.');
    }

    public function test_admin_and_operator_can_register_keyword(): void
    {
        foreach (['admin', 'operador'] as $index => $role) {
            $termo = "termo {$index}";

            $this->actingAs($this->usuarioInterno($role))
                ->post(route('admin.palavras-chave.store'), ['termo' => $termo])
                ->assertRedirect(route('admin.palavras-chave.index'))
                ->assertSessionHas('success', 'Palavra-chave cadastrada com sucesso.');

            $this->assertDatabaseHas('palavras_chave', ['termo' => $termo]);
        }
    }

    public function test_registration_validates_required_unique_and_maximum_length_term(): void
    {
        $this->palavraChave('termo existente');
        $usuario = $this->usuarioInterno();

        foreach ([null, 'termo existente', str_repeat('a', 256)] as $termo) {
            $this->actingAs($usuario)
                ->from(route('admin.palavras-chave.create'))
                ->post(route('admin.palavras-chave.store'), ['termo' => $termo])
                ->assertRedirect(route('admin.palavras-chave.create'))
                ->assertSessionHasErrors('termo');
        }

        $this->assertDatabaseCount('palavras_chave', 1);
    }

    public function test_admin_and_operator_can_update_keyword(): void
    {
        foreach (['admin', 'operador'] as $index => $role) {
            $palavraChave = $this->palavraChave("termo original {$index}");

            $this->actingAs($this->usuarioInterno($role))
                ->put(route('admin.palavras-chave.update', $palavraChave), [
                    'termo' => "termo atualizado {$index}",
                ])
                ->assertRedirect(route('admin.palavras-chave.index'))
                ->assertSessionHas('success', 'Palavra-chave atualizada com sucesso.');

            $this->assertDatabaseHas('palavras_chave', [
                'id' => $palavraChave->id,
                'termo' => "termo atualizado {$index}",
            ]);
        }
    }

    public function test_update_allows_keyword_to_keep_its_own_term(): void
    {
        $palavraChave = $this->palavraChave();

        $this->actingAs($this->usuarioInterno())
            ->put(route('admin.palavras-chave.update', $palavraChave), [
                'termo' => $palavraChave->termo,
            ])
            ->assertRedirect(route('admin.palavras-chave.index'));

        $this->assertDatabaseHas('palavras_chave', [
            'id' => $palavraChave->id,
            'termo' => $palavraChave->termo,
        ]);
    }

    public function test_update_rejects_invalid_or_another_keyword_term(): void
    {
        $palavraChave = $this->palavraChave('termo original');
        $this->palavraChave('termo existente');
        $usuario = $this->usuarioInterno();

        foreach ([null, 'termo existente', str_repeat('a', 256)] as $termo) {
            $this->actingAs($usuario)
                ->from(route('admin.palavras-chave.edit', $palavraChave))
                ->put(route('admin.palavras-chave.update', $palavraChave), ['termo' => $termo])
                ->assertRedirect(route('admin.palavras-chave.edit', $palavraChave))
                ->assertSessionHasErrors('termo');
        }

        $this->assertDatabaseHas('palavras_chave', [
            'id' => $palavraChave->id,
            'termo' => 'termo original',
        ]);
    }

    public function test_admin_and_operator_can_delete_keyword(): void
    {
        foreach (['admin', 'operador'] as $index => $role) {
            $palavraChave = $this->palavraChave("termo {$index}");

            $this->actingAs($this->usuarioInterno($role))
                ->delete(route('admin.palavras-chave.destroy', $palavraChave))
                ->assertRedirect(route('admin.palavras-chave.index'))
                ->assertSessionHas('success', 'Palavra-chave excluída com sucesso.');

            $this->assertDatabaseMissing('palavras_chave', ['id' => $palavraChave->id]);
        }
    }

    public function test_deleting_keyword_removes_association_without_deleting_item(): void
    {
        $palavraChave = $this->palavraChave();
        $item = $this->item();
        $palavraChave->itensAcervo()->attach($item);

        $this->actingAs($this->usuarioInterno())
            ->delete(route('admin.palavras-chave.destroy', $palavraChave))
            ->assertRedirect(route('admin.palavras-chave.index'));

        $this->assertDatabaseMissing('item_acervo_palavra_chave', [
            'palavra_chave_id' => $palavraChave->id,
            'item_acervo_id' => $item->id,
        ]);
        $this->assertDatabaseHas('item_acervos', ['id' => $item->id]);
    }
}
