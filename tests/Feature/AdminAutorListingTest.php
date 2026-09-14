<?php

namespace Tests\Feature;

use App\Models\Autor;
use App\Models\ItemAcervo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAutorListingTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioInterno(string $role = 'admin'): User
    {
        return User::factory()->create([
            'role' => $role,
            'status' => 'ativo',
        ]);
    }

    public function test_guest_cannot_access_author_listing(): void
    {
        $this
            ->get(route('admin.autores.index'))
            ->assertRedirect('/login');
    }

    public function test_admin_can_access_author_listing(): void
    {
        $this
            ->actingAs($this->usuarioInterno())
            ->get(route('admin.autores.index'))
            ->assertOk();
    }

    public function test_operator_can_access_author_listing(): void
    {
        $this
            ->actingAs($this->usuarioInterno('operador'))
            ->get(route('admin.autores.index'))
            ->assertOk();
    }

    public function test_author_listing_displays_authors(): void
    {
        Autor::create([
            'nome' => 'João da Silva',
            'tipo' => 'pessoa',
            'observacao' => 'Fotógrafo local.',
        ]);

        Autor::create([
            'nome' => 'Museu Municipal',
            'tipo' => 'instituicao',
            'observacao' => 'Instituição responsável pelo acervo.',
        ]);

        $this
            ->actingAs($this->usuarioInterno())
            ->get(route('admin.autores.index'))
            ->assertOk()
            ->assertSee('João da Silva')
            ->assertSee('Pessoa')
            ->assertSee('Museu Municipal')
            ->assertSee('Instituição');
    }

    public function test_author_listing_is_ordered_by_name(): void
    {
        Autor::create([
            'nome' => 'Zélia',
            'tipo' => 'pessoa',
        ]);

        Autor::create([
            'nome' => 'Antônio',
            'tipo' => 'pessoa',
        ]);

        Autor::create([
            'nome' => 'Maria',
            'tipo' => 'pessoa',
        ]);

        $response = $this
            ->actingAs($this->usuarioInterno())
            ->get(route('admin.autores.index'))
            ->assertOk();

        $response
            ->assertSeeInOrder([
                'Antônio',
                'Maria',
                'Zélia',
            ]);
    }

    public function test_author_listing_displays_number_of_associated_items(): void
    {
        $autor = Autor::create([
            'nome' => 'João da Silva',
            'tipo' => 'pessoa',
        ]);

        for ($i = 1; $i <= 3; $i++) {
            ItemAcervo::create([
                'tipo_item' => 'fotografia',
                'titulo' => "Fotografia {$i}",
                'tipo_data' => 'ano',
                'ano' => 1980,
                'estado_conservacao' => 'desconhecido',
                'status' => 'rascunho',
                'visibilidade' => 'privado',
                'autor_id' => $autor->id,
            ]);
        }

        $this
            ->actingAs($this->usuarioInterno())
            ->get(route('admin.autores.index'))
            ->assertOk()
            ->assertSee('João da Silva')
            ->assertSee('3');
    }

    public function test_author_listing_displays_empty_state(): void
    {
        $this
            ->actingAs($this->usuarioInterno())
            ->get(route('admin.autores.index'))
            ->assertOk()
            ->assertSee('Nenhum autor cadastrado.');
    }
}