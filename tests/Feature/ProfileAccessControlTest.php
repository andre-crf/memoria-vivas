<?php

namespace Tests\Feature;

use App\Models\Arquivo;
use App\Models\Assunto;
use App\Models\Autor;
use App\Models\Categoria;
use App\Models\Colecao;
use App\Models\ConjuntoContextual;
use App\Models\ItemAcervo;
use App\Models\PalavraChave;
use App\Models\Pessoa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ProfileAccessControlTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'ativo']);
    }

    private function operador(): User
    {
        return User::factory()->create(['role' => 'operador', 'status' => 'ativo']);
    }

    private function item(): ItemAcervo
    {
        return ItemAcervo::create([
            'titulo' => 'Praca central',
            'tipo_item' => 'fotografia',
            'tipo_data' => 'ano',
            'ano' => 1980,
            'status' => 'rascunho',
        ]);
    }

    private function arquivo(ItemAcervo $item): Arquivo
    {
        return Arquivo::create([
            'item_acervo_id' => $item->id,
            'nome_original' => 'praca.jpg',
            'provider' => 'local',
            'storage_path' => 'acervo/praca.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'tipo_arquivo' => 'imagem',
            'versao_arquivo' => 'original',
        ]);
    }

    public function test_admin_and_operator_can_manage_regular_item_actions(): void
    {
        $item = $this->item();

        foreach ([$this->admin(), $this->operador()] as $user) {
            $gate = Gate::forUser($user);

            $this->assertTrue($gate->allows('viewAny', ItemAcervo::class));
            $this->assertTrue($gate->allows('view', $item));
            $this->assertTrue($gate->allows('create', ItemAcervo::class));
            $this->assertTrue($gate->allows('update', $item));
            $this->assertTrue($gate->allows('updateVisibility', $item));
            $this->assertTrue($gate->allows('delete', $item));
        }
    }

    public function test_only_admin_can_restore_or_force_delete_items(): void
    {
        $item = $this->item();
        $admin = $this->admin();
        $operador = $this->operador();

        $this->assertTrue(Gate::forUser($admin)->allows('restore', $item));
        $this->assertTrue(Gate::forUser($admin)->allows('forceDelete', $item));
        $this->assertFalse(Gate::forUser($operador)->allows('restore', $item));
        $this->assertFalse(Gate::forUser($operador)->allows('forceDelete', $item));
    }

    public function test_admin_and_operator_can_manage_original_files(): void
    {
        $arquivo = $this->arquivo($this->item());

        foreach ([$this->admin(), $this->operador()] as $user) {
            $gate = Gate::forUser($user);

            $this->assertTrue($gate->allows('viewAny', Arquivo::class));
            $this->assertTrue($gate->allows('view', $arquivo));
            $this->assertTrue($gate->allows('create', Arquivo::class));
            $this->assertTrue($gate->allows('uploadOriginal', Arquivo::class));
            $this->assertTrue($gate->allows('update', $arquivo));
            $this->assertTrue($gate->allows('replaceOriginal', $arquivo));
            $this->assertTrue($gate->allows('delete', $arquivo));
        }
    }

    public function test_admin_and_operator_can_manage_support_catalogs(): void
    {
        $resources = [
            Categoria::create(['titulo' => 'Fotografia']),
            Assunto::create(['titulo' => 'Espaco urbano']),
            PalavraChave::create(['termo' => 'centro']),
            Pessoa::create(['nome' => 'Morador identificado']),
            Autor::create(['nome' => 'Fundacao Cultural', 'tipo' => 'instituicao']),
            Colecao::create(['titulo' => 'Umuarama nos anos 80']),
            ConjuntoContextual::create(['titulo' => 'Centro historico']),
        ];

        foreach ([$this->admin(), $this->operador()] as $user) {
            $gate = Gate::forUser($user);

            foreach ($resources as $resource) {
                $this->assertTrue($gate->allows('viewAny', $resource::class));
                $this->assertTrue($gate->allows('view', $resource));
                $this->assertTrue($gate->allows('create', $resource::class));
                $this->assertTrue($gate->allows('update', $resource));
                $this->assertTrue($gate->allows('delete', $resource));
            }
        }
    }

    public function test_only_admin_can_manage_users_and_view_audit(): void
    {
        $admin = $this->admin();
        $operador = $this->operador();
        $target = $this->operador();

        foreach (['viewAny', 'create'] as $ability) {
            $this->assertTrue(Gate::forUser($admin)->allows($ability, User::class));
            $this->assertFalse(Gate::forUser($operador)->allows($ability, User::class));
        }

        foreach (['view', 'update', 'updateIdentity', 'updateRole', 'updateStatus'] as $ability) {
            $this->assertTrue(Gate::forUser($admin)->allows($ability, $target));
            $this->assertFalse(Gate::forUser($operador)->allows($ability, $target));
        }

        $this->assertTrue(Gate::forUser($admin)->allows('viewAudit', User::class));
        $this->assertFalse(Gate::forUser($operador)->allows('viewAudit', User::class));
    }

    public function test_last_active_admin_cannot_be_downgraded_or_deactivated(): void
    {
        $admin = $this->admin();

        $this->assertFalse(Gate::forUser($admin)->allows('updateRole', [$admin, 'operador']));
        $this->assertFalse(Gate::forUser($admin)->allows('updateStatus', [$admin, 'inativo']));

        $secondAdmin = $this->admin();

        $this->assertTrue(Gate::forUser($secondAdmin)->allows('updateRole', [$admin, 'operador']));
        $this->assertTrue(Gate::forUser($secondAdmin)->allows('updateStatus', [$admin, 'inativo']));
    }

    public function test_users_cannot_change_another_user_password(): void
    {
        $admin = $this->admin();
        $operador = $this->operador();
        $target = $this->operador();

        $this->assertFalse(Gate::forUser($admin)->allows('updatePassword', $target));
        $this->assertFalse(Gate::forUser($operador)->allows('updatePassword', $target));
    }

    public function test_internal_users_can_manage_their_own_password_flow(): void
    {
        foreach ([$this->admin(), $this->operador()] as $user) {
            $gate = Gate::forUser($user);

            $this->assertTrue($gate->allows('updatePassword', $user));
            $this->assertTrue($gate->allows('resetOwnPassword', User::class));
        }
    }

    public function test_inactive_internal_users_cannot_use_profile_permissions(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'status' => 'inativo']);
        $item = $this->item();

        $this->assertFalse(Gate::forUser($user)->allows('viewAny', ItemAcervo::class));
        $this->assertFalse(Gate::forUser($user)->allows('update', $item));
        $this->assertFalse(Gate::forUser($user)->allows('viewAny', User::class));
    }

    public function test_can_middleware_redirects_guests_and_forbids_disallowed_profile_actions(): void
    {
        Route::get('/_permission-test/users', fn (): string => 'ok')
            ->middleware(['web', 'auth', 'can:viewAny,'.User::class]);

        $this->get('/_permission-test/users')
            ->assertRedirect('/login');

        $this
            ->actingAs($this->operador())
            ->get('/_permission-test/users')
            ->assertForbidden();

        $this
            ->actingAs($this->admin())
            ->get('/_permission-test/users')
            ->assertOk()
            ->assertSee('ok');
    }
}
