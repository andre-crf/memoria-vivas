<?php

namespace Tests\Feature;

use App\Enums\Visibilidade;
use App\Models\ItemAcervo;
use App\Models\Pessoa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFotografiaPersonAssociationTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioInterno(string $role = 'admin'): User
    {
        return User::factory()->create([
            'role' => $role,
            'status' => 'ativo',
        ]);
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    private function fotografia(array $dados = []): ItemAcervo
    {
        return ItemAcervo::create($dados + [
            'titulo' => 'Praça central em obras',
            'tipo_item' => 'fotografia',
            'tipo_data' => 'ano',
            'ano' => 1980,
            'estado_conservacao' => 'bom',
            'status' => 'rascunho',
            'visibilidade' => Visibilidade::Privado,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $override = []): array
    {
        return $override + [
            'titulo' => 'Praça central restaurada',
            'tipo_data' => 'ano',
            'ano' => 1981,
            'estado_conservacao' => 'regular',
            'status' => 'publicado',
            'visibilidade' => Visibilidade::Publico->value,
        ];
    }

    public function test_create_form_shows_person_options(): void
    {
        Pessoa::create(['nome' => 'Maria Souza']);
        Pessoa::create(['nome' => 'João Silva']);

        $this
            ->actingAs($this->usuarioInterno())
            ->get(route('admin.fotografias.create'))
            ->assertOk()
            ->assertSee('Pessoas identificadas')
            ->assertSee('Maria Souza')
            ->assertSee('João Silva')
            ->assertSee('name="pessoa_ids[]"', false);
    }

    public function test_internal_user_can_register_photograph_with_multiple_people(): void
    {
        $pessoas = [
            Pessoa::create(['nome' => 'Maria Souza']),
            Pessoa::create(['nome' => 'João Silva']),
        ];

        $this
            ->actingAs($this->usuarioInterno('operador'))
            ->post(route('admin.fotografias.store'), $this->validPayload([
                'titulo' => 'Fotografia com pessoas',
                'pessoa_ids' => [$pessoas[0]->id, $pessoas[1]->id],
            ]))
            ->assertRedirect(route('admin.fotografias.index'));

        $fotografia = ItemAcervo::where('titulo', 'Fotografia com pessoas')->firstOrFail();

        $this->assertCount(2, $fotografia->pessoas);
        $this->assertTrue($fotografia->pessoas->contains($pessoas[0]));
        $this->assertTrue($fotografia->pessoas->contains($pessoas[1]));
    }

    public function test_internal_user_can_register_photograph_without_people(): void
    {
        $this
            ->actingAs($this->usuarioInterno())
            ->post(route('admin.fotografias.store'), $this->validPayload([
                'titulo' => 'Fotografia sem pessoas identificadas',
            ]))
            ->assertRedirect(route('admin.fotografias.index'));

        $fotografia = ItemAcervo::where('titulo', 'Fotografia sem pessoas identificadas')->firstOrFail();

        $this->assertCount(0, $fotografia->pessoas);
    }

    public function test_edit_form_loads_existing_people(): void
    {
        $pessoa = Pessoa::create(['nome' => 'Maria Souza']);
        $fotografia = $this->fotografia();
        $fotografia->pessoas()->attach($pessoa);

        $this
            ->actingAs($this->usuarioInterno())
            ->get(route('admin.fotografias.edit', $fotografia))
            ->assertOk()
            ->assertSee('Pessoas identificadas')
            ->assertSee('Maria Souza')
            ->assertSee('name="pessoa_ids[]"', false)
            ->assertSee('checked', false);
    }

    public function test_internal_user_can_replace_and_remove_people(): void
    {
        $pessoaAntiga = Pessoa::create(['nome' => 'Pessoa antiga']);
        $pessoaNova = Pessoa::create(['nome' => 'Pessoa nova']);
        $fotografia = $this->fotografia();
        $fotografia->pessoas()->attach($pessoaAntiga);

        $this
            ->actingAs($this->usuarioInterno())
            ->followingRedirects()
            ->put(route('admin.fotografias.update', $fotografia), $this->validPayload([
                'pessoa_ids' => [$pessoaNova->id],
            ]))
            ->assertOk()
            ->assertSee('Pessoa nova')
            ->assertDontSee('Pessoa antiga');

        $fotografia->refresh();

        $this->assertTrue($fotografia->pessoas->contains($pessoaNova));
        $this->assertFalse($fotografia->pessoas->contains($pessoaAntiga));

        $this
            ->actingAs($this->usuarioInterno())
            ->followingRedirects()
            ->put(route('admin.fotografias.update', $fotografia), $this->validPayload([
                'pessoa_ids' => [],
            ]))
            ->assertOk()
            ->assertSee('Nenhuma pessoa vinculada.')
            ->assertDontSee('Pessoa nova');

        $fotografia->refresh();

        $this->assertCount(0, $fotografia->pessoas);
    }

    public function test_person_values_must_exist_and_not_be_duplicated(): void
    {
        $pessoa = Pessoa::create(['nome' => 'Maria Souza']);

        $this
            ->actingAs($this->usuarioInterno())
            ->from(route('admin.fotografias.create'))
            ->post(route('admin.fotografias.store'), $this->validPayload([
                'pessoa_ids' => [$pessoa->id, $pessoa->id, 999],
            ]))
            ->assertRedirect(route('admin.fotografias.create'))
            ->assertSessionHasErrors([
                'pessoa_ids.0',
                'pessoa_ids.1',
                'pessoa_ids.2',
            ]);

        $this->assertDatabaseMissing('item_acervos', [
            'titulo' => 'Praça central restaurada',
        ]);
    }
}
