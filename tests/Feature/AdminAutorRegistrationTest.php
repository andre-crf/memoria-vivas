<?php

namespace Tests\Feature;

use App\Models\Autor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAutorRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioInterno(string $role = 'admin'): User
    {
        return User::factory()->create([
            'role' => $role,
            'status' => 'ativo',
        ]);
    }

    public function test_guest_cannot_access_author_creation_form(): void
    {
        $this
            ->get(route('admin.autores.create'))
            ->assertRedirect('/login');
    }

    public function test_admin_can_access_author_creation_form(): void
    {
        $this
            ->actingAs($this->usuarioInterno())
            ->get(route('admin.autores.create'))
            ->assertOk();
    }

    public function test_operator_can_access_author_creation_form(): void
    {
        $this
            ->actingAs($this->usuarioInterno('operador'))
            ->get(route('admin.autores.create'))
            ->assertOk();
    }

    public function test_admin_can_register_person_author(): void
    {
        $this
            ->actingAs($this->usuarioInterno())
            ->post(route('admin.autores.store'), [
                'nome' => 'João da Silva',
                'tipo' => 'pessoa',
                'observacao' => 'Fotógrafo local.',
            ])
            ->assertRedirect(route('admin.autores.index'))
            ->assertSessionHas('success', 'Autor cadastrado com sucesso.');

        $this->assertDatabaseHas('autores', [
            'nome' => 'João da Silva',
            'tipo' => 'pessoa',
            'observacao' => 'Fotógrafo local.',
        ]);
    }

    public function test_operator_can_register_institution_author(): void
    {
        $this
            ->actingAs($this->usuarioInterno('operador'))
            ->post(route('admin.autores.store'), [
                'nome' => 'Museu Municipal',
                'tipo' => 'instituicao',
                'observacao' => 'Instituição responsável pelo acervo.',
            ])
            ->assertRedirect(route('admin.autores.index'));

        $this->assertDatabaseHas('autores', [
            'nome' => 'Museu Municipal',
            'tipo' => 'instituicao',
            'observacao' => 'Instituição responsável pelo acervo.',
        ]);
    }

    public function test_name_is_required(): void
    {
        $this
            ->actingAs($this->usuarioInterno())
            ->post(route('admin.autores.store'), [
                'nome' => '',
                'tipo' => 'pessoa',
                'observacao' => null,
            ])
            ->assertSessionHasErrors('nome');

        $this->assertDatabaseCount('autores', 0);
    }

    public function test_type_is_required(): void
    {
        $this
            ->actingAs($this->usuarioInterno())
            ->post(route('admin.autores.store'), [
                'nome' => 'João da Silva',
                'tipo' => '',
                'observacao' => null,
            ])
            ->assertSessionHasErrors('tipo');

        $this->assertDatabaseCount('autores', 0);
    }

    public function test_type_must_be_valid(): void
    {
        $this
            ->actingAs($this->usuarioInterno())
            ->post(route('admin.autores.store'), [
                'nome' => 'João da Silva',
                'tipo' => 'empresa',
                'observacao' => null,
            ])
            ->assertSessionHasErrors('tipo');

        $this->assertDatabaseCount('autores', 0);
    }

    public function test_name_cannot_exceed_255_characters(): void
    {
        $this
            ->actingAs($this->usuarioInterno())
            ->post(route('admin.autores.store'), [
                'nome' => str_repeat('A', 256),
                'tipo' => 'pessoa',
                'observacao' => null,
            ])
            ->assertSessionHasErrors('nome');

        $this->assertDatabaseCount('autores', 0);
    }

    public function test_observation_is_optional(): void
    {
        $this
            ->actingAs($this->usuarioInterno())
            ->post(route('admin.autores.store'), [
                'nome' => 'João da Silva',
                'tipo' => 'pessoa',
                'observacao' => null,
            ])
            ->assertRedirect(route('admin.autores.index'));

        $this->assertDatabaseHas('autores', [
            'nome' => 'João da Silva',
            'tipo' => 'pessoa',
            'observacao' => null,
        ]);
    }
}