<?php

namespace Tests\Feature;

use App\Enums\Visibilidade;
use App\Models\ItemAcervo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFotografiaFormExperienceTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioInterno(string $role = 'operador'): User
    {
        return User::factory()->create([
            'role' => $role,
            'status' => 'ativo',
        ]);
    }

    public function test_create_and_edit_forms_expose_compact_searchable_relationship_sections(): void
    {
        $user = $this->usuarioInterno();
        $fotografia = ItemAcervo::create([
            'tipo_item' => 'fotografia',
            'titulo' => 'Estação ferroviária',
            'tipo_data' => 'desconhecida',
            'estado_conservacao' => 'desconhecido',
            'status' => 'rascunho',
            'visibilidade' => Visibilidade::Privado,
        ]);

        foreach ([route('admin.fotografias.create'), route('admin.fotografias.edit', $fotografia)] as $url) {
            $this->actingAs($user)
                ->get($url)
                ->assertOk()
                ->assertSee('data-relationship-picker', false)
                ->assertSee('data-option-search', false)
                ->assertSee('Cadastrar novo autor')
                ->assertSee('Criar categoria')
                ->assertSee('Criar assunto')
                ->assertSee('Criar palavra-chave');
        }
    }

    public function test_internal_user_can_create_author_and_classifications_from_photograph_form(): void
    {
        $user = $this->usuarioInterno();

        $this->actingAs($user)
            ->postJson(route('admin.autores.store'), [
                'nome' => 'Arquivo Municipal',
                'tipo' => 'instituicao',
            ])
            ->assertCreated()
            ->assertJson([
                'label' => 'Arquivo Municipal',
                'description' => 'Instituição',
            ]);

        $this->actingAs($user)
            ->postJson(route('admin.categorias.store'), ['titulo' => 'Paisagem urbana'])
            ->assertCreated()
            ->assertJson(['label' => 'Paisagem urbana']);

        $this->actingAs($user)
            ->postJson(route('admin.assuntos.store'), ['titulo' => 'Patrimônio ferroviário'])
            ->assertCreated()
            ->assertJson(['label' => 'Patrimônio ferroviário']);

        $this->actingAs($user)
            ->postJson(route('admin.palavras-chave.store'), ['termo' => 'estação'])
            ->assertCreated()
            ->assertJson(['label' => 'estação']);

        $this->assertDatabaseHas('autores', ['nome' => 'Arquivo Municipal']);
        $this->assertDatabaseHas('categorias', ['titulo' => 'Paisagem urbana']);
        $this->assertDatabaseHas('assuntos', ['titulo' => 'Patrimônio ferroviário']);
        $this->assertDatabaseHas('palavras_chave', ['termo' => 'estação']);
    }

    public function test_quick_creation_returns_validation_errors_as_json(): void
    {
        $this->actingAs($this->usuarioInterno())
            ->postJson(route('admin.categorias.store'), ['titulo' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('titulo');
    }

    public function test_photograph_form_rejects_invalid_decade_and_long_legend_with_portuguese_messages(): void
    {
        $payload = [
            'titulo' => 'Fotografia com validação',
            'tipo_data' => 'decada',
            'decada' => '19a0',
            'estado_conservacao' => 'desconhecido',
            'status' => 'rascunho',
            'visibilidade' => Visibilidade::Privado->value,
            'legenda' => str_repeat('a', 5001),
        ];

        $response = $this->actingAs($this->usuarioInterno())
            ->from(route('admin.fotografias.create'))
            ->post(route('admin.fotografias.store'), $payload);

        $response
            ->assertRedirect(route('admin.fotografias.create'))
            ->assertSessionHasErrors([
                'decada' => 'A década deve ter quatro dígitos e terminar em zero, como 1980.',
                'legenda' => 'A legenda/descrição não pode ultrapassar 5.000 caracteres.',
            ]);

        $this->assertDatabaseMissing('item_acervos', ['titulo' => 'Fotografia com validação']);
    }

    public function test_photograph_form_accepts_a_valid_decade(): void
    {
        $this->actingAs($this->usuarioInterno())
            ->post(route('admin.fotografias.store'), [
                'titulo' => 'Fotografia da década de 1980',
                'tipo_data' => 'decada',
                'decada' => '1980',
                'estado_conservacao' => 'desconhecido',
                'status' => 'rascunho',
                'visibilidade' => Visibilidade::Privado->value,
                'legenda' => str_repeat('a', 5000),
            ])
            ->assertRedirect(route('admin.fotografias.index'));

        $this->assertDatabaseHas('item_acervos', [
            'titulo' => 'Fotografia da década de 1980',
            'decada' => '1980',
        ]);
    }
}
