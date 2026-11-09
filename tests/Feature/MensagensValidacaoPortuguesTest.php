<?php

namespace Tests\Feature;

use App\Livewire\Admin\CatalogPicker;
use App\Models\Categoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Mensagens de validação em português (lang/pt_BR): padrão do Laravel traduzido, nomes de campo como
 * aparecem nas telas e mensagens personalizadas dos Form Requests preservadas.
 */
class MensagensValidacaoPortuguesTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_locale_is_brazilian_portuguese(): void
    {
        $this->assertSame('pt_BR', app()->getLocale());
        $this->assertSame('O campo título é obrigatório.', Validator::make([], ['titulo' => 'required'])->errors()->first('titulo'));
        $this->assertSame('O campo e-mail deve ser um endereço de e-mail válido.', Validator::make(['email' => 'x'], ['email' => 'email'])->errors()->first('email'));
        $this->assertSame('O campo legenda/descrição não pode ter mais de 3 caracteres.', Validator::make(['legenda' => 'abcd'], ['legenda' => 'max:3'])->errors()->first('legenda'));
        $this->assertSame('As credenciais informadas não conferem.', __('auth.failed'));
    }

    public function test_admin_forms_show_portuguese_messages(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin', 'status' => 'ativo']));
        Categoria::create(['titulo' => 'Praças']);

        $this->from(route('admin.categorias.create'))->followingRedirects()
            ->post(route('admin.categorias.store'), ['titulo' => ''])
            ->assertSee('O campo título é obrigatório.')
            ->assertDontSee('The título field');

        $this->from(route('admin.categorias.create'))->followingRedirects()
            ->post(route('admin.categorias.store'), ['titulo' => 'Praças'])
            ->assertSee('O valor informado em título já está em uso.');

        $this->from(route('admin.usuarios.create'))->followingRedirects()
            ->post(route('admin.usuarios.store'), ['nome' => 'Ana', 'email' => 'ana@example.test', 'role' => 'operador', 'password' => 'Senha-Segura-1', 'password_confirmation' => 'outra'])
            ->assertSee('A confirmação do campo senha não corresponde.');

        $this->from(route('admin.fotografias.create'))->followingRedirects()
            ->post(route('admin.fotografias.store'), ['titulo' => '', 'tipo_data' => 'ano', 'ano' => 'abc', 'estado_conservacao' => 'bom', 'status' => 'rascunho', 'visibilidade' => 'privado'])
            ->assertSee('O campo título é obrigatório.')
            ->assertSee('O ano deve ser um número inteiro válido.');
    }

    public function test_custom_messages_and_profile_error_bags_are_preserved(): void
    {
        $usuario = User::factory()->create(['role' => 'operador', 'status' => 'ativo']);
        $this->actingAs($usuario);

        // Mensagens próprias do Form Request continuam valendo, cada uma no seu bag.
        $this->from(route('admin.perfil.edit'))->put(route('admin.perfil.password.update'), ['current_password' => 'errada', 'password' => 'curta', 'password_confirmation' => 'curta'])
            ->assertSessionHasErrorsIn('passwordUpdate', ['current_password' => 'A senha atual está incorreta.', 'password' => 'A nova senha deve ter pelo menos 8 caracteres.']);
        $this->from(route('admin.perfil.edit'))->put(route('admin.perfil.update'), ['nome' => '', 'email' => 'x'])
            ->assertSessionHasErrorsIn('profileUpdate', ['nome' => 'O nome é obrigatório.', 'email' => 'Informe um endereço de e-mail válido.']);

        // Login: mensagem própria do controller.
        auth()->logout();
        $this->from(route('login'))->followingRedirects()->post(route('login.store'), ['email' => 'ninguem@example.test', 'password' => 'x'])
            ->assertSee('As credenciais informadas não conferem ou o usuário está inativo.');
        $this->from(route('login'))->followingRedirects()->post(route('login.store'), ['email' => '', 'password' => ''])
            ->assertSee('O campo e-mail é obrigatório.')
            ->assertSee('O campo senha é obrigatório.');
    }

    public function test_public_forms_and_quick_create_show_portuguese_messages(): void
    {
        Storage::fake('local');

        $this->from(route('public.colaborar'))->followingRedirects()->post(route('public.colaborar.store'), [
            'nome' => '',
            'email' => 'invalido',
            'titulo' => 'Praça',
            'local' => str_repeat('a', 181),
            'fotos' => [UploadedFile::fake()->create('documento.pdf', 10, 'application/pdf')],
        ])
            ->assertSee('O campo nome é obrigatório.')
            ->assertSee('O campo e-mail deve ser um endereço de e-mail válido.')
            ->assertSee('O campo local retratado não pode ter mais de 180 caracteres.')
            // Mensagens personalizadas do controller continuam valendo.
            ->assertSee('Envie imagens JPG, PNG ou WebP.')
            ->assertSee('Confirme que você tem permissão para compartilhar as fotografias.');

        Livewire::actingAs(User::factory()->create(['role' => 'admin', 'status' => 'ativo']));
        Livewire::test(CatalogPicker::class, ['catalog' => 'categoria', 'initialSelected' => null])
            ->set('form.titulo', '')
            ->call('save')
            ->assertSee('O campo título é obrigatório.')
            ->assertDontSee('form.titulo field');
    }
}
