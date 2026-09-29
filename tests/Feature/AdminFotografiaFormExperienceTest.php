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
}
