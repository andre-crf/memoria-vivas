<?php

namespace Tests\Feature;

use App\Enums\Visibilidade;
use App\Models\Arquivo;
use App\Models\ItemAcervo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicAreaTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_home_is_available_without_authentication(): void
    {
        $this
            ->get(route('public.home'))
            ->assertOk()
            ->assertSee('Memórias Vivas de Umuarama')
            ->assertSee('images/memorias-vivas-logo.jpg', false)
            ->assertSee('Explorar o catálogo')
            ->assertSee('Uma porta de entrada para a memória local')
            ->assertSee('Navegação pública', false)
            ->assertSee('Buscar no catálogo')
            ->assertSee(route('public.catalogo'), false)
            ->assertSee('Área administrativa')
            ->assertDontSee('Administração do acervo');
    }

    public function test_authenticated_user_can_access_public_home_without_being_redirected(): void
    {
        $user = User::factory()->create(['role' => 'operador', 'status' => 'ativo']);

        $this
            ->actingAs($user)
            ->get(route('public.home'))
            ->assertOk();
    }

    public function test_public_home_displays_up_to_five_recent_public_photographs(): void
    {
        $fotografias = collect(range(1, 6))->map(function (int $numero): ItemAcervo {
            $fotografia = $this->fotografia("Fotografia recente {$numero}");

            Arquivo::create([
                'item_acervo_id' => $fotografia->id,
                'provider' => 'local',
                'storage_path' => "acervo/{$fotografia->id}/thumbnail.jpg",
                'mime_type' => 'image/jpeg',
                'file_size' => 100,
                'tipo_arquivo' => 'imagem',
                'versao_arquivo' => 'thumbnail',
                'width' => 800,
                'height' => 600,
            ]);

            return $fotografia;
        });

        $this
            ->get(route('public.home'))
            ->assertOk()
            ->assertSee('Fotografias recentes')
            ->assertSee('Fotografia recente 6')
            ->assertSee('Fotografia recente 2')
            ->assertDontSee('Fotografia recente 1')
            ->assertSee('data-carousel-next', false)
            ->assertSee(route('public.fotografias.show', $fotografias->last()), false);
    }

    public function test_public_photograph_detail_is_available_only_for_eligible_records(): void
    {
        $fotografia = $this->fotografia('Detalhe público');
        Arquivo::create([
            'item_acervo_id' => $fotografia->id,
            'provider' => 'local',
            'storage_path' => "acervo/{$fotografia->id}/medium.jpg",
            'mime_type' => 'image/jpeg',
            'file_size' => 100,
            'tipo_arquivo' => 'imagem',
            'versao_arquivo' => 'medium',
            'width' => 800,
            'height' => 600,
        ]);

        $this
            ->get(route('public.fotografias.show', $fotografia))
            ->assertOk()
            ->assertSee('Detalhe público')
            ->assertSee('Fotografia do acervo');

        $fotografia->update(['visibilidade' => Visibilidade::Privado]);

        $this->get(route('public.fotografias.show', $fotografia))->assertNotFound();
    }

    public function test_public_catalog_accepts_search_term_without_authentication(): void
    {
        $this
            ->get(route('public.catalogo', ['q' => 'praça']))
            ->assertOk()
            ->assertSee('Busca por “praça”.')
            ->assertSee('Nenhum item público disponível ainda');
    }

    private function fotografia(string $titulo): ItemAcervo
    {
        return ItemAcervo::create([
            'titulo' => $titulo,
            'tipo_item' => 'fotografia',
            'tipo_data' => 'desconhecida',
            'status' => 'publicado',
            'visibilidade' => Visibilidade::Publico,
        ]);
    }
}
