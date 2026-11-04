<?php

namespace Tests\Feature;

use App\Models\ItemAcervo;
use App\Models\Pessoa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPessoaCrudTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioInterno(string $role = 'admin'): User
    {
        return User::factory()->create([
            'role' => $role,
            'status' => 'ativo',
        ]);
    }

    private function pessoa(string $nome = 'Maria Souza'): Pessoa
    {
        return Pessoa::create([
            'nome' => $nome,
            'observacao' => 'Moradora identificada na fotografia.',
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

    public function test_guest_is_redirected_from_person_routes(): void
    {
        $pessoa = $this->pessoa();

        $this->get(route('admin.pessoas.index'))->assertRedirect('/login');
        $this->get(route('admin.pessoas.create'))->assertRedirect('/login');
        $this->post(route('admin.pessoas.store'))->assertRedirect('/login');
        $this->get(route('admin.pessoas.edit', $pessoa))->assertRedirect('/login');
        $this->put(route('admin.pessoas.update', $pessoa))->assertRedirect('/login');
        $this->delete(route('admin.pessoas.destroy', $pessoa))->assertRedirect('/login');

        $this->assertDatabaseHas('pessoas', ['id' => $pessoa->id]);
    }

    public function test_admin_and_operator_can_access_person_listing_and_forms(): void
    {
        $pessoa = $this->pessoa();

        foreach (['admin', 'operador'] as $role) {
            $usuario = $this->usuarioInterno($role);

            $this->actingAs($usuario)
                ->get(route('admin.pessoas.index'))
                ->assertOk()
                ->assertSee('Pessoas identificadas')
                ->assertSee('Nova pessoa');

            $this->actingAs($usuario)
                ->get(route('admin.pessoas.create'))
                ->assertOk()
                ->assertSee('Cadastrar pessoa');

            $this->actingAs($usuario)
                ->get(route('admin.pessoas.edit', $pessoa))
                ->assertOk()
                ->assertSee('Editar pessoa')
                ->assertSee($pessoa->nome)
                ->assertSee($pessoa->observacao);
        }
    }

    public function test_listing_displays_people_in_name_order_with_observations(): void
    {
        $this->pessoa('Zélia Alves');
        $this->pessoa('Antônio Lima');

        $this->actingAs($this->usuarioInterno())
            ->get(route('admin.pessoas.index'))
            ->assertOk()
            ->assertSeeInOrder(['Antônio Lima', 'Zélia Alves'])
            ->assertSee('Moradora identificada na fotografia.');
    }

    public function test_listing_displays_empty_state(): void
    {
        $this->actingAs($this->usuarioInterno())
            ->get(route('admin.pessoas.index'))
            ->assertOk()
            ->assertSee('Nenhuma pessoa cadastrada');
    }

    public function test_listing_displays_associated_item_count(): void
    {
        $pessoa = $this->pessoa();
        $pessoa->itensAcervo()->attach([
            $this->item('Fotografia 1')->id,
            $this->item('Fotografia 2')->id,
        ]);

        $this->actingAs($this->usuarioInterno())
            ->get(route('admin.pessoas.index'))
            ->assertOk()
            ->assertSee($pessoa->nome)
            ->assertSee('2');
    }

    public function test_delete_confirmation_reports_zero_one_and_multiple_associations(): void
    {
        $this->pessoa('Pessoa sem associação');
        $umaAssociacao = $this->pessoa('Pessoa com uma associação');
        $variasAssociacoes = $this->pessoa('Pessoa com várias associações');

        $umaAssociacao->itensAcervo()->attach($this->item('Fotografia 1'));
        $variasAssociacoes->itensAcervo()->attach([
            $this->item('Fotografia 2')->id,
            $this->item('Fotografia 3')->id,
        ]);

        $this->actingAs($this->usuarioInterno())
            ->get(route('admin.pessoas.index'))
            ->assertOk()
            ->assertSee('Esta pessoa não possui itens associados.')
            ->assertSee('Esta pessoa está associada a 1 item do acervo.')
            ->assertSee('Esta pessoa está associada a 2 itens do acervo.');
    }

    public function test_admin_and_operator_can_register_person(): void
    {
        foreach (['admin', 'operador'] as $index => $role) {
            $nome = "Pessoa {$index}";

            $this->actingAs($this->usuarioInterno($role))
                ->post(route('admin.pessoas.store'), [
                    'nome' => $nome,
                    'observacao' => 'Observação complementar.',
                ])
                ->assertRedirect(route('admin.pessoas.index'))
                ->assertSessionHas('success', 'Pessoa cadastrada com sucesso.');

            $this->assertDatabaseHas('pessoas', [
                'nome' => $nome,
                'observacao' => 'Observação complementar.',
            ]);
        }
    }

    public function test_registration_allows_homonyms(): void
    {
        $this->pessoa('Maria Souza');

        $this->actingAs($this->usuarioInterno())
            ->post(route('admin.pessoas.store'), [
                'nome' => 'Maria Souza',
                'observacao' => 'Outra pessoa com o mesmo nome.',
            ])
            ->assertRedirect(route('admin.pessoas.index'));

        $this->assertDatabaseCount('pessoas', 2);
    }

    public function test_registration_validates_name_and_observation(): void
    {
        $usuario = $this->usuarioInterno();

        foreach ([
            ['nome' => null, 'observacao' => null, 'error' => 'nome'],
            ['nome' => ['Maria'], 'observacao' => null, 'error' => 'nome'],
            ['nome' => str_repeat('a', 256), 'observacao' => null, 'error' => 'nome'],
            ['nome' => 'Maria Souza', 'observacao' => ['texto'], 'error' => 'observacao'],
        ] as $payload) {
            $this->actingAs($usuario)
                ->post(route('admin.pessoas.store'), $payload)
                ->assertSessionHasErrors($payload['error']);
        }

        $this->assertDatabaseCount('pessoas', 0);
    }

    public function test_observation_is_optional(): void
    {
        $this->actingAs($this->usuarioInterno())
            ->post(route('admin.pessoas.store'), [
                'nome' => 'Maria Souza',
                'observacao' => '',
            ])
            ->assertRedirect(route('admin.pessoas.index'));

        $this->assertDatabaseHas('pessoas', [
            'nome' => 'Maria Souza',
            'observacao' => null,
        ]);
    }

    public function test_admin_and_operator_can_update_person(): void
    {
        foreach (['admin', 'operador'] as $index => $role) {
            $pessoa = $this->pessoa("Pessoa original {$index}");

            $this->actingAs($this->usuarioInterno($role))
                ->put(route('admin.pessoas.update', $pessoa), [
                    'nome' => "Pessoa atualizada {$index}",
                    'observacao' => 'Observação atualizada.',
                ])
                ->assertRedirect(route('admin.pessoas.index'))
                ->assertSessionHas('success', 'Pessoa atualizada com sucesso.');

            $this->assertDatabaseHas('pessoas', [
                'id' => $pessoa->id,
                'nome' => "Pessoa atualizada {$index}",
                'observacao' => 'Observação atualizada.',
            ]);
        }
    }

    public function test_update_validates_data_and_allows_removing_observation(): void
    {
        $pessoa = $this->pessoa();
        $usuario = $this->usuarioInterno();

        $this->actingAs($usuario)
            ->put(route('admin.pessoas.update', $pessoa), [
                'nome' => str_repeat('a', 256),
                'observacao' => ['texto'],
            ])
            ->assertSessionHasErrors(['nome', 'observacao']);

        $this->actingAs($usuario)
            ->put(route('admin.pessoas.update', $pessoa), [
                'nome' => 'Maria Souza',
                'observacao' => '',
            ])
            ->assertRedirect(route('admin.pessoas.index'));

        $this->assertDatabaseHas('pessoas', [
            'id' => $pessoa->id,
            'observacao' => null,
        ]);
    }

    public function test_admin_and_operator_can_delete_person(): void
    {
        foreach (['admin', 'operador'] as $index => $role) {
            $pessoa = $this->pessoa("Pessoa {$index}");

            $this->actingAs($this->usuarioInterno($role))
                ->delete(route('admin.pessoas.destroy', $pessoa))
                ->assertRedirect(route('admin.pessoas.index'))
                ->assertSessionHas('success', 'Pessoa excluída com sucesso.');

            $this->assertDatabaseMissing('pessoas', ['id' => $pessoa->id]);
        }
    }

    public function test_deleting_person_removes_associations_without_deleting_items(): void
    {
        $pessoa = $this->pessoa();
        $item = $this->item();
        $pessoa->itensAcervo()->attach($item);

        $this->actingAs($this->usuarioInterno())
            ->delete(route('admin.pessoas.destroy', $pessoa))
            ->assertRedirect(route('admin.pessoas.index'));

        $this->assertDatabaseMissing('item_acervo_pessoa', [
            'pessoa_id' => $pessoa->id,
            'item_acervo_id' => $item->id,
        ]);
        $this->assertDatabaseHas('item_acervos', ['id' => $item->id]);
    }
}
