<?php

namespace Tests\Feature;

use App\Enums\Visibilidade;
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
use Tests\TestCase;

class AdminFotografiaDetailsTest extends TestCase
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
            'titulo' => 'Praça central em obras',
            'tipo_item' => 'fotografia',
            'legenda' => 'Registro da reforma da praça central.',
            'tipo_data' => 'data_exata',
            'dia' => 15,
            'mes' => 3,
            'ano' => 1980,
            'local_atual' => 'Praça Santos Dumont',
            'local_epoca' => 'Praça Central',
            'evento' => 'Reforma urbana',
            'cedente' => 'Família Silva',
            'estado_conservacao' => 'bom',
            'status' => 'publicado',
            'visibilidade' => Visibilidade::Publico,
        ]);
    }

    public function test_guest_is_redirected_from_photograph_details(): void
    {
        $fotografia = $this->fotografia();

        $this
            ->get(route('admin.fotografias.show', $fotografia))
            ->assertRedirect('/login');
    }

    public function test_internal_user_can_view_photograph_details(): void
    {
        $this
            ->actingAs($this->usuarioInterno('operador'))
            ->get(route('admin.fotografias.show', $this->fotografia()))
            ->assertOk()
            ->assertSee('Dados de catalogação')
            ->assertSee('Praça central em obras')
            ->assertSee('15/03/1980')
            ->assertSee('Publicado')
            ->assertSee('Público')
            ->assertSee('Editar')
            ->assertSee('Excluir');
    }

    public function test_details_show_basic_catalog_information_and_audit_users(): void
    {
        $creator = $this->usuarioInterno();
        $updater = $this->usuarioInterno();

        $this->actingAs($creator);
        $fotografia = $this->fotografia();

        $this->actingAs($updater);
        $fotografia->update(['evento' => 'Reforma da praça']);

        $this
            ->actingAs($this->usuarioInterno())
            ->get(route('admin.fotografias.show', $fotografia))
            ->assertOk()
            ->assertSee('Praça central em obras')
            ->assertSee('Registro da reforma da praça central.')
            ->assertSee('15/03/1980')
            ->assertSee('Data exata')
            ->assertSee('Praça Santos Dumont')
            ->assertSee('Praça Central')
            ->assertSee('Reforma da praça')
            ->assertSee('Família Silva')
            ->assertSee('Bom')
            ->assertSee('Criado em')
            ->assertSee('Última alteração')
            ->assertSee($creator->nome)
            ->assertSee($updater->nome);
    }

    public function test_details_are_ready_to_show_classifications_people_author_and_files(): void
    {
        $autor = Autor::create(['nome' => 'Fundação Cultural', 'tipo' => 'instituicao']);
        $categoria = Categoria::create(['titulo' => 'Fotografia urbana']);
        $assunto = Assunto::create(['titulo' => 'Espaço público']);
        $palavraChave = PalavraChave::create(['termo' => 'centro']);
        $pessoa = Pessoa::create(['nome' => 'Maria Souza']);
        $colecao = Colecao::create(['titulo' => 'Umuarama antiga']);
        $conjunto = ConjuntoContextual::create(['titulo' => 'Centro histórico']);

        $fotografia = $this->fotografia(['autor_id' => $autor->id]);
        $fotografia->categorias()->attach($categoria);
        $fotografia->assuntos()->attach($assunto);
        $fotografia->palavrasChave()->attach($palavraChave);
        $fotografia->pessoas()->attach($pessoa);
        $fotografia->colecoes()->attach($colecao);
        $fotografia->conjuntosContextuais()->attach($conjunto, ['ordem' => 2]);

        Arquivo::create([
            'item_acervo_id' => $fotografia->id,
            'nome_original' => 'praca-central.jpg',
            'provider' => 'local',
            'storage_path' => 'acervo/praca-central.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 2048,
            'tipo_arquivo' => 'imagem',
            'versao_arquivo' => 'original',
        ]);

        $this
            ->actingAs($this->usuarioInterno())
            ->get(route('admin.fotografias.show', $fotografia))
            ->assertOk()
            ->assertSee('Classificações')
            ->assertSee('Fotografia urbana')
            ->assertSee('Espaço público')
            ->assertSee('centro')
            ->assertSee('Pessoas e autoria')
            ->assertSee('Fundação Cultural')
            ->assertSee('Maria Souza')
            ->assertSee('Umuarama antiga')
            ->assertSee('Centro histórico')
            ->assertSee('Ordem 2')
            ->assertSee('Arquivos')
            ->assertSee('praca-central.jpg')
            ->assertSee('2,0 KB');
    }

    public function test_details_do_not_open_non_photograph_items(): void
    {
        $documento = $this->fotografia([
            'titulo' => 'Documento administrativo',
            'tipo_item' => 'documento',
        ]);

        $this
            ->actingAs($this->usuarioInterno())
            ->get(route('admin.fotografias.show', $documento))
            ->assertNotFound();
    }

    public function test_delete_action_soft_deletes_photograph(): void
    {
        $user = $this->usuarioInterno();
        $fotografia = $this->fotografia(['titulo' => 'Fotografia para exclusão']);

        $this
            ->actingAs($user)
            ->delete(route('admin.fotografias.destroy', $fotografia))
            ->assertRedirect(route('admin.fotografias.index'));

        $this->assertSoftDeleted('item_acervos', [
            'id' => $fotografia->id,
        ]);

        $this->assertDatabaseHas('item_acervos', [
            'id' => $fotografia->id,
            'deleted_by_user_id' => $user->id,
        ]);
    }
}
