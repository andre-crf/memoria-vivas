<?php

namespace Tests\Feature;

use App\Models\Assunto;
use App\Models\ItemAcervo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAssuntoCrudTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioInterno(string $role = 'admin'): User
    {
        return User::factory()->create([
            'role' => $role,
            'status' => 'ativo',
        ]);
    }

    private function assunto(string $titulo = 'Patrimônio histórico'): Assunto
    {
        return Assunto::create([
            'titulo' => $titulo,
            'descricao' => 'Descrição do assunto.',
        ]);
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

    public function test_guest_is_redirected_from_subject_routes(): void
    {
        $assunto = $this->assunto();

        $this->get(route('admin.assuntos.index'))->assertRedirect('/login');
        $this->get(route('admin.assuntos.create'))->assertRedirect('/login');
        $this->post(route('admin.assuntos.store'))->assertRedirect('/login');
        $this->get(route('admin.assuntos.edit', $assunto))->assertRedirect('/login');
        $this->put(route('admin.assuntos.update', $assunto))->assertRedirect('/login');
        $this->delete(route('admin.assuntos.destroy', $assunto))->assertRedirect('/login');

        $this->assertDatabaseHas('assuntos', ['id' => $assunto->id]);
    }

    public function test_admin_and_operator_can_access_subject_listing_and_forms(): void
    {
        $assunto = $this->assunto();

        foreach (['admin', 'operador'] as $role) {
            $usuario = $this->usuarioInterno($role);

            $this->actingAs($usuario)
                ->get(route('admin.assuntos.index'))
                ->assertOk()
                ->assertSee('Assuntos')
                ->assertSee('Novo assunto');

            $this->actingAs($usuario)
                ->get(route('admin.assuntos.create'))
                ->assertOk()
                ->assertSee('Cadastrar assunto');

            $this->actingAs($usuario)
                ->get(route('admin.assuntos.edit', $assunto))
                ->assertOk()
                ->assertSee('Editar assunto')
                ->assertSee($assunto->titulo)
                ->assertSee('Salvar alterações');
        }
    }

    public function test_listing_displays_subjects_in_title_order(): void
    {
        $this->assunto('Memória oral');
        $this->assunto('Arquitetura');

        $this->actingAs($this->usuarioInterno())
            ->get(route('admin.assuntos.index'))
            ->assertOk()
            ->assertSeeInOrder(['Arquitetura', 'Memória oral']);
    }

    public function test_listing_displays_empty_state(): void
    {
        $this->actingAs($this->usuarioInterno())
            ->get(route('admin.assuntos.index'))
            ->assertOk()
            ->assertSee('Nenhum assunto cadastrado');
    }

    public function test_listing_displays_associated_item_count_and_delete_confirmation(): void
    {
        $assunto = $this->assunto();
        $assunto->itensAcervo()->attach([
            $this->item('Fotografia 1')->id,
            $this->item('Fotografia 2')->id,
        ]);

        $this->actingAs($this->usuarioInterno())
            ->get(route('admin.assuntos.index'))
            ->assertOk()
            ->assertSee($assunto->titulo)
            ->assertSee('2')
            ->assertSee('Tem certeza que deseja excluir este assunto?')
            ->assertSee('As associações com os itens do acervo serão removidas.');
    }

    public function test_admin_and_operator_can_register_subject(): void
    {
        foreach (['admin', 'operador'] as $index => $role) {
            $titulo = "Assunto {$index}";

            $this->actingAs($this->usuarioInterno($role))
                ->post(route('admin.assuntos.store'), [
                    'titulo' => $titulo,
                    'descricao' => 'Descrição cadastrada.',
                ])
                ->assertRedirect(route('admin.assuntos.index'))
                ->assertSessionHas('success', 'Assunto cadastrado com sucesso.');

            $this->assertDatabaseHas('assuntos', [
                'titulo' => $titulo,
                'descricao' => 'Descrição cadastrada.',
            ]);
        }
    }

    public function test_registration_allows_empty_description(): void
    {
        $this->actingAs($this->usuarioInterno())
            ->post(route('admin.assuntos.store'), [
                'titulo' => 'Sem descrição',
                'descricao' => '',
            ])
            ->assertRedirect(route('admin.assuntos.index'));

        $this->assertDatabaseHas('assuntos', [
            'titulo' => 'Sem descrição',
            'descricao' => null,
        ]);
    }

    public function test_registration_validates_required_unique_and_maximum_length_title(): void
    {
        $this->assunto('Assunto existente');
        $usuario = $this->usuarioInterno();

        foreach ([null, 'Assunto existente', str_repeat('a', 256)] as $titulo) {
            $this->actingAs($usuario)
                ->from(route('admin.assuntos.create'))
                ->post(route('admin.assuntos.store'), [
                    'titulo' => $titulo,
                    'descricao' => 'Descrição.',
                ])
                ->assertRedirect(route('admin.assuntos.create'))
                ->assertSessionHasErrors('titulo');
        }

        $this->assertDatabaseCount('assuntos', 1);
    }

    public function test_admin_and_operator_can_update_subject(): void
    {
        foreach (['admin', 'operador'] as $index => $role) {
            $assunto = $this->assunto("Assunto original {$index}");

            $this->actingAs($this->usuarioInterno($role))
                ->put(route('admin.assuntos.update', $assunto), [
                    'titulo' => "Assunto atualizado {$index}",
                    'descricao' => 'Descrição atualizada.',
                ])
                ->assertRedirect(route('admin.assuntos.index'))
                ->assertSessionHas('success', 'Assunto atualizado com sucesso.');

            $this->assertDatabaseHas('assuntos', [
                'id' => $assunto->id,
                'titulo' => "Assunto atualizado {$index}",
                'descricao' => 'Descrição atualizada.',
            ]);
        }
    }

    public function test_update_allows_own_title_and_empty_description(): void
    {
        $assunto = $this->assunto();

        $this->actingAs($this->usuarioInterno())
            ->put(route('admin.assuntos.update', $assunto), [
                'titulo' => $assunto->titulo,
                'descricao' => '',
            ])
            ->assertRedirect(route('admin.assuntos.index'));

        $this->assertDatabaseHas('assuntos', [
            'id' => $assunto->id,
            'titulo' => $assunto->titulo,
            'descricao' => null,
        ]);
    }

    public function test_update_rejects_invalid_or_another_subject_title(): void
    {
        $assunto = $this->assunto('Assunto original');
        $this->assunto('Assunto existente');
        $usuario = $this->usuarioInterno();

        foreach ([null, 'Assunto existente', str_repeat('a', 256)] as $titulo) {
            $this->actingAs($usuario)
                ->from(route('admin.assuntos.edit', $assunto))
                ->put(route('admin.assuntos.update', $assunto), [
                    'titulo' => $titulo,
                    'descricao' => 'Descrição alterada.',
                ])
                ->assertRedirect(route('admin.assuntos.edit', $assunto))
                ->assertSessionHasErrors('titulo');
        }

        $this->assertDatabaseHas('assuntos', [
            'id' => $assunto->id,
            'titulo' => 'Assunto original',
            'descricao' => 'Descrição do assunto.',
        ]);
    }

    public function test_admin_and_operator_can_delete_subject(): void
    {
        foreach (['admin', 'operador'] as $index => $role) {
            $assunto = $this->assunto("Assunto {$index}");

            $this->actingAs($this->usuarioInterno($role))
                ->delete(route('admin.assuntos.destroy', $assunto))
                ->assertRedirect(route('admin.assuntos.index'))
                ->assertSessionHas('success', 'Assunto excluído com sucesso.');

            $this->assertDatabaseMissing('assuntos', ['id' => $assunto->id]);
        }
    }

    public function test_deleting_subject_removes_association_without_deleting_item(): void
    {
        $assunto = $this->assunto();
        $item = $this->item();
        $assunto->itensAcervo()->attach($item);

        $this->actingAs($this->usuarioInterno())
            ->delete(route('admin.assuntos.destroy', $assunto))
            ->assertRedirect(route('admin.assuntos.index'));

        $this->assertDatabaseMissing('assunto_item_acervo', [
            'assunto_id' => $assunto->id,
            'item_acervo_id' => $item->id,
        ]);
        $this->assertDatabaseHas('item_acervos', ['id' => $item->id]);
    }
}
