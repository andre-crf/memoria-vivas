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
                ->assertSee('x-data="relationshipPicker(', false)
                ->assertSee('x-data="characterCounter(', false)
                ->assertSee('x-data="historicalDateFields(', false)
                ->assertSeeInOrder([
                    'x-data="filePreview()"',
                    'id="arquivo_original"',
                    '@change="selectFile($event)"',
                ], false)
                ->assertSee('Cadastrar novo autor')
                ->assertSee('Criar categoria')
                ->assertSee('Criar assunto')
                ->assertSee('Criar palavra-chave');
        }
    }

    public function test_photograph_can_be_submitted_without_opening_or_filling_quick_creation_fields(): void
    {
        $user = $this->usuarioInterno();

        $form = $this->actingAs($user)
            ->get(route('admin.fotografias.create'))
            ->assertOk();

        preg_match_all('/<input\b[^>]*wire:model="form\.(?:nome|titulo|termo)"[^>]*>/s', $form->getContent(), $quickCreationInputs);

        $this->assertCount(4, $quickCreationInputs[0]);

        foreach ($quickCreationInputs[0] as $input) {
            $this->assertDoesNotMatchRegularExpression('/\srequired(?:\s|=|>)/', $input);
            $this->assertDoesNotMatchRegularExpression('/\sname=/', $input);
        }

        $form
            ->assertDontSee('data-endpoint=', false)
            ->assertDontSee('data-quick-submit', false)
            ->assertDontSee('data-quick-field', false);

        $this->actingAs($user)
            ->post(route('admin.fotografias.store'), [
                'titulo' => 'Fotografia sem cadastros rápidos',
                'tipo_data' => 'desconhecida',
                'estado_conservacao' => 'desconhecido',
                'status' => 'rascunho',
                'visibilidade' => Visibilidade::Privado->value,
            ])
            ->assertRedirect(route('admin.fotografias.index'));

        $this->assertDatabaseHas('item_acervos', [
            'titulo' => 'Fotografia sem cadastros rápidos',
            'tipo_item' => 'fotografia',
        ]);
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
