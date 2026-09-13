<?php

namespace Tests\Feature;

use App\Models\Autor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAutorUpdateTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioInterno(string $role = 'admin'): User
    {
        return User::factory()->create([
            'role' => $role,
            'status' => 'ativo',
        ]);
    }

    public function test_guest_cannot_access_author_edit_form(): void
    {
        $autor = Autor::create([
            'nome' => 'João da Silva',
            'tipo' => 'pessoa',
            'observacao' => 'Fotógrafo local.',
        ]);

        $this
            ->get(route('admin.autores.edit', $autor))
            ->assertRedirect('/login');
    }

    public function test_admin_can_access_author_edit_form(): void
    {
        $autor = Autor::create([
            'nome' => 'João da Silva',
            'tipo' => 'pessoa',
        ]);

        $this
            ->actingAs($this->usuarioInterno())
            ->get(route('admin.autores.edit', $autor))
            ->assertOk()
            ->assertSee('João da Silva')
            ->assertSee('Pessoa');
    }

    public function test_operator_can_access_author_edit_form(): void
    {
        $autor = Autor::create([
            'nome' => 'Museu Municipal',
            'tipo' => 'instituicao',
        ]);

        $this
            ->actingAs($this->usuarioInterno('operador'))
            ->get(route('admin.autores.edit', $autor))
            ->assertOk()
            ->assertSee('Museu Municipal')
            ->assertSee('Instituição');
    }

    public function test_admin_can_update_author(): void
    {
        $autor = Autor::create([
            'nome' => 'João da Silva',
            'tipo' => 'pessoa',
            'observacao' => 'Observação antiga.',
        ]);

        $this
            ->actingAs($this->usuarioInterno())
            ->put(route('admin.autores.update', $autor), [
                'nome' => 'João da Silva Atualizado',
                'tipo' => 'pessoa',
                'observacao' => 'Nova observação.',
            ])
            ->assertRedirect(route('admin.autores.index'))
            ->assertSessionHas('success', 'Autor atualizado com sucesso.');

        $this->assertDatabaseHas('autores', [
            'id' => $autor->id,
            'nome' => 'João da Silva Atualizado',
            'tipo' => 'pessoa',
            'observacao' => 'Nova observação.',
        ]);
    }

    public function test_operator_can_update_author(): void
    {
        $autor = Autor::create([
            'nome' => 'Museu Municipal',
            'tipo' => 'instituicao',
            'observacao' => 'Observação antiga.',
        ]);

        $this
            ->actingAs($this->usuarioInterno('operador'))
            ->put(route('admin.autores.update', $autor), [
                'nome' => 'Museu Histórico Municipal',
                'tipo' => 'instituicao',
                'observacao' => 'Nova observação.',
            ])
            ->assertRedirect(route('admin.autores.index'));

        $this->assertDatabaseHas('autores', [
            'id' => $autor->id,
            'nome' => 'Museu Histórico Municipal',
            'tipo' => 'instituicao',
            'observacao' => 'Nova observação.',
        ]);
    }

    public function test_name_is_required_when_updating_author(): void
    {
        $autor = Autor::create([
            'nome' => 'João da Silva',
            'tipo' => 'pessoa',
        ]);

        $this
            ->actingAs($this->usuarioInterno())
            ->put(route('admin.autores.update', $autor), [
                'nome' => '',
                'tipo' => 'pessoa',
                'observacao' => null,
            ])
            ->assertSessionHasErrors('nome');

        $this->assertDatabaseHas('autores', [
            'id' => $autor->id,
            'nome' => 'João da Silva',
        ]);
    }

    public function test_type_is_required_when_updating_author(): void
    {
        $autor = Autor::create([
            'nome' => 'João da Silva',
            'tipo' => 'pessoa',
        ]);

        $this
            ->actingAs($this->usuarioInterno())
            ->put(route('admin.autores.update', $autor), [
                'nome' => 'João da Silva',
                'tipo' => '',
                'observacao' => null,
            ])
            ->assertSessionHasErrors('tipo');

        $this->assertDatabaseHas('autores', [
            'id' => $autor->id,
            'tipo' => 'pessoa',
        ]);
    }

    public function test_type_must_be_valid_when_updating_author(): void
    {
        $autor = Autor::create([
            'nome' => 'João da Silva',
            'tipo' => 'pessoa',
        ]);

        $this
            ->actingAs($this->usuarioInterno())
            ->put(route('admin.autores.update', $autor), [
                'nome' => 'João da Silva',
                'tipo' => 'empresa',
                'observacao' => null,
            ])
            ->assertSessionHasErrors('tipo');

        $this->assertDatabaseHas('autores', [
            'id' => $autor->id,
            'tipo' => 'pessoa',
        ]);
    }

    public function test_name_cannot_exceed_255_characters_when_updating_author(): void
    {
        $autor = Autor::create([
            'nome' => 'João da Silva',
            'tipo' => 'pessoa',
        ]);

        $this
            ->actingAs($this->usuarioInterno())
            ->put(route('admin.autores.update', $autor), [
                'nome' => str_repeat('A', 256),
                'tipo' => 'pessoa',
                'observacao' => null,
            ])
            ->assertSessionHasErrors('nome');

        $this->assertDatabaseHas('autores', [
            'id' => $autor->id,
            'nome' => 'João da Silva',
        ]);
    }

    public function test_observation_can_be_empty_when_updating_author(): void
    {
        $autor = Autor::create([
            'nome' => 'João da Silva',
            'tipo' => 'pessoa',
            'observacao' => 'Observação antiga.',
        ]);

        $this
            ->actingAs($this->usuarioInterno())
            ->put(route('admin.autores.update', $autor), [
                'nome' => 'João da Silva',
                'tipo' => 'pessoa',
                'observacao' => null,
            ])
            ->assertRedirect(route('admin.autores.index'));

        $this->assertDatabaseHas('autores', [
            'id' => $autor->id,
            'nome' => 'João da Silva',
            'observacao' => null,
        ]);
    }
}