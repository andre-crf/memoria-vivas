<?php

namespace Tests\Feature;

use App\Enums\Visibilidade;
use App\Models\ItemAcervo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFotografiaListingTest extends TestCase
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
            'titulo' => fake()->sentence(3),
            'tipo_item' => 'fotografia',
            'tipo_data' => 'ano',
            'ano' => 1980,
            'status' => 'rascunho',
            'visibilidade' => Visibilidade::Privado,
        ]);
    }

    public function test_guest_is_redirected_from_admin_photographs_listing(): void
    {
        $this
            ->get(route('admin.fotografias.index'))
            ->assertRedirect('/login');
    }

    public function test_internal_users_can_access_admin_photographs_listing(): void
    {
        foreach (['admin', 'operador'] as $role) {
            $this
                ->actingAs($this->usuarioInterno($role))
                ->get(route('admin.fotografias.index'))
                ->assertOk()
                ->assertSee('Fotografias cadastradas');
        }
    }

    public function test_listing_shows_registered_photograph_fields_and_actions(): void
    {
        $this->fotografia([
            'titulo' => 'Praça central em obras',
            'tipo_data' => 'data_exata',
            'dia' => 15,
            'mes' => 3,
            'ano' => 1980,
            'status' => 'publicado',
            'visibilidade' => Visibilidade::Publico,
        ]);

        $this
            ->actingAs($this->usuarioInterno())
            ->get(route('admin.fotografias.index'))
            ->assertOk()
            ->assertSee('Praça central em obras')
            ->assertSee('15/03/1980')
            ->assertSee('Publicado')
            ->assertSee('Público')
            ->assertSee('Visualizar')
            ->assertSee('Editar')
            ->assertSee('Excluir');
    }

    public function test_listing_only_shows_non_deleted_photographs(): void
    {
        $this->fotografia(['titulo' => 'Fotografia visível']);
        $this->fotografia(['titulo' => 'Fotografia removida'])->delete();

        ItemAcervo::create([
            'titulo' => 'Documento fora da listagem',
            'tipo_item' => 'documento',
            'tipo_data' => 'ano',
            'ano' => 1980,
            'status' => 'rascunho',
            'visibilidade' => Visibilidade::Privado,
        ]);

        $this
            ->actingAs($this->usuarioInterno())
            ->get(route('admin.fotografias.index'))
            ->assertOk()
            ->assertSee('Fotografia visível')
            ->assertDontSee('Fotografia removida')
            ->assertDontSee('Documento fora da listagem');
    }

    public function test_listing_has_empty_state_when_there_are_no_photographs(): void
    {
        $this
            ->actingAs($this->usuarioInterno())
            ->get(route('admin.fotografias.index'))
            ->assertOk()
            ->assertSee('Nenhuma fotografia cadastrada');
    }

    public function test_listing_is_paginated(): void
    {
        for ($i = 1; $i <= 16; $i++) {
            $this->fotografia(['titulo' => sprintf('Foto pagina %02d', $i)]);
        }

        $this
            ->actingAs($this->usuarioInterno())
            ->get(route('admin.fotografias.index'))
            ->assertOk()
            ->assertSee('Foto pagina 16')
            ->assertDontSee('Foto pagina 01');
    }
}
