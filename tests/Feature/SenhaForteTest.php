<?php

namespace Tests\Feature;

use App\Models\User;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SenhaForteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string, string}>
     */
    public static function senhasFracas(): array
    {
        return [
            'curta' => ['Ab-1', 'A nova senha deve ter pelo menos 8 caracteres.'],
            'sem maiúscula' => ['senha-forte-1', 'O campo nova senha deve conter pelo menos uma letra maiúscula e uma minúscula.'],
            'sem minúscula' => ['SENHA-FORTE-1', 'O campo nova senha deve conter pelo menos uma letra maiúscula e uma minúscula.'],
            'sem número' => ['Senha-Forte', 'O campo nova senha deve conter pelo menos um número.'],
            'sem símbolo' => ['SenhaForte1', 'O campo nova senha deve conter pelo menos um símbolo.'],
        ];
    }

    #[DataProvider('senhasFracas')]
    public function test_profile_rejects_weak_password_with_portuguese_message(string $senha, string $mensagem): void
    {
        $usuario = User::factory()->create(['role' => 'operador', 'status' => 'ativo']);

        $this->actingAs($usuario)
            ->from(route('admin.perfil.edit'))
            ->put(route('admin.perfil.password.update'), [
                'current_password' => 'password',
                'password' => $senha,
                'password_confirmation' => $senha,
            ])
            ->assertRedirect(route('admin.perfil.edit'))
            ->assertSessionHasErrorsIn('passwordUpdate', ['password' => $mensagem]);

        $this->assertTrue(Hash::check('password', $usuario->fresh()->password));
    }

    public function test_user_creation_rejects_weak_password_and_accepts_strong_one(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'ativo']);
        $dados = ['nome' => 'Nova pessoa', 'email' => 'nova@example.test', 'role' => 'operador'];

        $this->actingAs($admin)
            ->from(route('admin.usuarios.create'))
            ->post(route('admin.usuarios.store'), $dados + ['password' => 'senhafraca', 'password_confirmation' => 'senhafraca'])
            ->assertSessionHasErrors(['password' => 'O campo senha deve conter pelo menos uma letra maiúscula e uma minúscula.']);
        $this->assertDatabaseMissing('users', ['email' => 'nova@example.test']);

        $this->actingAs($admin)
            ->post(route('admin.usuarios.store'), $dados + ['password' => 'Senha forte 1', 'password_confirmation' => 'Senha forte 1'])
            ->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('Senha forte 1', User::where('email', 'nova@example.test')->value('password')));
    }

    public function test_login_does_not_apply_strength_rule_to_existing_passwords(): void
    {
        User::factory()->create(['email' => 'antiga@example.test', 'password' => Hash::make('senhaantiga'), 'status' => 'ativo']);

        $this->post(route('login.store'), ['email' => 'antiga@example.test', 'password' => 'senhaantiga'])
            ->assertSessionHasNoErrors();
        $this->assertAuthenticated();
    }

    public function test_password_component_keeps_toggle_outside_accessible_name(): void
    {
        $xpath = $this->render('<x-catalog.password id="password" label="Senha" autocomplete="current-password" required :marcador="false" />');

        $campo = $this->first($xpath, '//input[@id="password"]');
        $this->assertSame('password', $campo->getAttribute('type'));
        $this->assertSame('password', $campo->getAttribute('name'));
        $this->assertSame('current-password', $campo->getAttribute('autocomplete'));
        $this->assertTrue($campo->hasAttribute('required'));
        $this->assertFalse($campo->hasAttribute('aria-describedby'));

        $rotulo = $this->first($xpath, '//label[@for="password"]');
        $this->assertSame('Senha', trim($rotulo->textContent), 'Sem marcador "obrigatório" quando marcador=false.');
        $this->assertSame(0, $xpath->query('.//button', $rotulo)->length, 'O botão não pode entrar no nome acessível do campo.');

        $botao = $this->first($xpath, '//button[@data-password-toggle]');
        $this->assertSame('button', $botao->getAttribute('type'));
        $this->assertSame('Mostrar senha', $botao->getAttribute('aria-label'));
        $this->assertSame('false', $botao->getAttribute('aria-pressed'));
        $this->assertSame('password', $botao->getAttribute('aria-controls'));
        $this->assertSame(2, $xpath->query('.//svg[@aria-hidden="true"]', $botao)->length);

        // Sem requisitos por padrão (login e senha atual).
        $this->assertSame(0, $xpath->query('//*[@data-password-requirements]')->length);
    }

    public function test_password_component_describes_requirements_and_error(): void
    {
        $erros = (new ViewErrorBag)->put('passwordUpdate', new MessageBag(['password' => ['O campo nova senha deve conter pelo menos um número.']]));

        $xpath = $this->render(
            '<x-catalog.password id="password" label="Nova senha" autocomplete="new-password" required requisitos minlength="8" error-bag="passwordUpdate" />',
            ['errors' => $erros],
        );

        $campo = $this->first($xpath, '//input[@id="password"]');
        $this->assertSame('8', $campo->getAttribute('minlength'));
        $this->assertSame('true', $campo->getAttribute('aria-invalid'));
        $this->assertSame('password-requisitos password-erro', $campo->getAttribute('aria-describedby'));

        $requisitos = $this->first($xpath, '//*[@id="password-requisitos"]');
        $this->assertSame(['tamanho', 'caixa', 'numero', 'simbolo'], array_map(
            fn (DOMElement $item): string => $item->getAttribute('data-requisito'),
            iterator_to_array($xpath->query('.//li', $requisitos)),
        ));
        $this->assertSame(4, $xpath->query('.//li[@data-ok="false"]', $requisitos)->length);

        $erro = $this->first($xpath, '//*[@id="password-erro"]');
        $this->assertSame('O campo nova senha deve conter pelo menos um número.', trim($erro->textContent));
        $this->assertSame(0, $xpath->query('ancestor::label', $erro)->length);
    }

    public function test_password_fields_use_component_in_admin_screens(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'ativo']);

        $this->get(route('login'))->assertOk()->assertSee('data-password-toggle', false);

        $this->actingAs($admin);
        $criar = $this->get(route('admin.usuarios.create'))->assertOk()->getContent();
        $this->assertSame(2, substr_count($criar, 'data-password-toggle'));
        $this->assertSame(1, substr_count($criar, 'data-password-requirements'));

        $perfil = $this->get(route('admin.perfil.edit'))->assertOk()->getContent();
        $this->assertSame(3, substr_count($perfil, 'data-password-toggle'));
        $this->assertSame(1, substr_count($perfil, 'data-password-requirements'));
    }

    private function render(string $blade, array $dados = []): DOMXPath
    {
        // Os componentes aninhados leem $errors dos dados compartilhados, como numa requisição real.
        View::share('errors', $dados['errors'] ?? new ViewErrorBag);
        $html = Blade::render($blade, $dados);
        $dom = new DOMDocument;
        @$dom->loadHTML('<?xml encoding="utf-8"?><html><body>'.$html.'</body></html>', LIBXML_NOERROR);

        return new DOMXPath($dom);
    }

    private function first(DOMXPath $xpath, string $query): DOMElement
    {
        $node = $xpath->query($query)->item(0);
        $this->assertInstanceOf(DOMElement::class, $node, "Elemento não encontrado: {$query}");

        return $node;
    }
}
