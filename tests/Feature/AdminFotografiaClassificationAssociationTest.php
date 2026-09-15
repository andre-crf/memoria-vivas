<?php

namespace Tests\Feature;

use App\Enums\Visibilidade;
use App\Models\Assunto;
use App\Models\Categoria;
use App\Models\ItemAcervo;
use App\Models\PalavraChave;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFotografiaClassificationAssociationTest extends TestCase
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

    public function test_create_form_shows_classification_options(): void
    {
        Categoria::create(['titulo' => 'Fotografia urbana']);
        Assunto::create(['titulo' => 'Espaço público']);
        PalavraChave::create(['termo' => 'centro']);

        $this
            ->actingAs($this->usuarioInterno())
            ->get(route('admin.fotografias.create'))
            ->assertOk()
            ->assertSee('Classificações')
            ->assertSee('Fotografia urbana')
            ->assertSee('Espaço público')
            ->assertSee('centro');
    }

    public function test_internal_user_can_register_photograph_with_multiple_classifications(): void
    {
        $categorias = [
            Categoria::create(['titulo' => 'Fotografia urbana']),
            Categoria::create(['titulo' => 'Arquitetura']),
        ];
        $assuntos = [
            Assunto::create(['titulo' => 'Espaço público']),
            Assunto::create(['titulo' => 'Memória local']),
        ];
        $palavrasChave = [
            PalavraChave::create(['termo' => 'centro']),
            PalavraChave::create(['termo' => 'praça']),
        ];

        $this
            ->actingAs($this->usuarioInterno('operador'))
            ->post(route('admin.fotografias.store'), $this->validPayload([
                'titulo' => 'Fotografia classificada',
                'categoria_ids' => [$categorias[0]->id, $categorias[1]->id],
                'assunto_ids' => [$assuntos[0]->id, $assuntos[1]->id],
                'palavra_chave_ids' => [$palavrasChave[0]->id, $palavrasChave[1]->id],
            ]))
            ->assertRedirect(route('admin.fotografias.index'));

        $fotografia = ItemAcervo::where('titulo', 'Fotografia classificada')->firstOrFail();

        $this->assertCount(2, $fotografia->categorias);
        $this->assertCount(2, $fotografia->assuntos);
        $this->assertCount(2, $fotografia->palavrasChave);
    }

    public function test_edit_form_loads_existing_classifications(): void
    {
        $categoria = Categoria::create(['titulo' => 'Fotografia urbana']);
        $assunto = Assunto::create(['titulo' => 'Espaço público']);
        $palavraChave = PalavraChave::create(['termo' => 'centro']);
        $fotografia = $this->fotografia();
        $fotografia->categorias()->attach($categoria);
        $fotografia->assuntos()->attach($assunto);
        $fotografia->palavrasChave()->attach($palavraChave);

        $this
            ->actingAs($this->usuarioInterno())
            ->get(route('admin.fotografias.edit', $fotografia))
            ->assertOk()
            ->assertSee('Fotografia urbana')
            ->assertSee('Espaço público')
            ->assertSee('centro')
            ->assertSee('name="categoria_ids[]"', false)
            ->assertSee('name="assunto_ids[]"', false)
            ->assertSee('name="palavra_chave_ids[]"', false)
            ->assertSee('checked', false);
    }

    public function test_internal_user_can_replace_and_remove_classifications(): void
    {
        $categoriaAntiga = Categoria::create(['titulo' => 'Categoria antiga']);
        $categoriaNova = Categoria::create(['titulo' => 'Categoria nova']);
        $assuntoAntigo = Assunto::create(['titulo' => 'Assunto antigo']);
        $palavraChaveAntiga = PalavraChave::create(['termo' => 'antiga']);
        $fotografia = $this->fotografia();
        $fotografia->categorias()->attach($categoriaAntiga);
        $fotografia->assuntos()->attach($assuntoAntigo);
        $fotografia->palavrasChave()->attach($palavraChaveAntiga);

        $this
            ->actingAs($this->usuarioInterno())
            ->followingRedirects()
            ->put(route('admin.fotografias.update', $fotografia), $this->validPayload([
                'categoria_ids' => [$categoriaNova->id],
                'assunto_ids' => [],
                'palavra_chave_ids' => [],
            ]))
            ->assertOk()
            ->assertSee('Categoria nova')
            ->assertDontSee('Categoria antiga')
            ->assertSee('Nenhum assunto vinculado.')
            ->assertSee('Nenhuma palavra-chave vinculada.');

        $fotografia->refresh();

        $this->assertTrue($fotografia->categorias->contains($categoriaNova));
        $this->assertFalse($fotografia->categorias->contains($categoriaAntiga));
        $this->assertCount(0, $fotografia->assuntos);
        $this->assertCount(0, $fotografia->palavrasChave);
    }

    public function test_classification_values_must_exist_and_not_be_duplicated(): void
    {
        $categoria = Categoria::create(['titulo' => 'Fotografia urbana']);
        $assunto = Assunto::create(['titulo' => 'Espaço público']);
        $palavraChave = PalavraChave::create(['termo' => 'centro']);

        $this
            ->actingAs($this->usuarioInterno())
            ->from(route('admin.fotografias.create'))
            ->post(route('admin.fotografias.store'), $this->validPayload([
                'categoria_ids' => [$categoria->id, $categoria->id],
                'assunto_ids' => [999],
                'palavra_chave_ids' => [$palavraChave->id, $palavraChave->id, 999],
            ]))
            ->assertRedirect(route('admin.fotografias.create'))
            ->assertSessionHasErrors([
                'categoria_ids.0',
                'categoria_ids.1',
                'assunto_ids.0',
                'palavra_chave_ids.0',
                'palavra_chave_ids.1',
                'palavra_chave_ids.2',
            ]);

        $this->assertDatabaseMissing('item_acervos', [
            'titulo' => 'Praça central restaurada',
        ]);
        $this->assertDatabaseHas('assuntos', [
            'id' => $assunto->id,
        ]);
    }
}
