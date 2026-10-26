<?php

namespace Tests\Feature;

use App\Enums\Visibilidade;
use App\Models\Arquivo;
use App\Models\ItemAcervo;
use App\Models\User;
use DOMDocument;
use DOMXPath;
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
            ->assertSee('images/memorias-vivas-logo.jpeg', false)
            ->assertSee('Pular para o conteúdo')
            ->assertSee('Explorar o catálogo')
            ->assertSee('Uma porta de entrada para a memória local')
            ->assertSee('Navegação pública', false)
            ->assertSee('Pesquisar no acervo')
            ->assertSee(route('public.catalogo'), false)
            ->assertSee('Área administrativa')
            ->assertDontSee('Administração do acervo');
    }

    public function test_about_page_lists_the_documented_institutional_references(): void
    {
        $response = $this->get(route('public.sobre'))
            ->assertOk()
            ->assertSee('Referências institucionais')
            ->assertSee('Instituto Federal do Paraná — Campus Umuarama')
            ->assertSee('Fundação Cultural de Umuarama')
            ->assertSee('Centro de Documentação Histórica de Umuarama');

        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8"'.$response->getContent());
        $xpath = new DOMXPath($document);

        $items = $xpath->query('//*[@id="instituicoes"]//ul/li');
        $links = $xpath->query('//*[@id="instituicoes"]//ul/li/a[@target="_blank" and @rel="noopener noreferrer"]');

        $this->assertCount(3, $items);
        $this->assertCount(3, $links);
        $this->assertCount(3, $xpath->query('//*[@id="instituicoes"]//img[@alt=""]'));
        $this->assertCount(1, $xpath->query('//*[@id="instituicoes"]//li[contains(., "Centro de Documentação Histórica")]//img[contains(@src, "cedhu-transparent.png")]'));
    }

    public function test_home_keeps_the_existing_hero_until_an_approved_photo_is_configured(): void
    {
        config()->set('hero.slides', [
            ['imagem' => 'images/hero/nao-existe.jpg', 'alt' => 'Fotografia ausente'],
            ['imagem' => '../.env', 'alt' => 'Caminho inválido'],
        ]);

        $this->get(route('public.home'))
            ->assertOk()
            ->assertSee('Identidade visual do projeto')
            ->assertDontSee('data-hero-carousel', false);
    }

    public function test_one_configured_hero_photo_has_context_without_redundant_controls(): void
    {
        config()->set('hero.slides', [[
            'imagem' => 'images/memorias-vivas-logo.jpeg',
            'alt' => 'Fotografia de referência',
            'titulo' => 'Praça central',
            'descricao' => 'Umuarama, década de 1970.',
        ]]);

        $this->get(route('public.home'))
            ->assertOk()
            ->assertSee('data-hero-carousel', false)
            ->assertSee('Fotografia de referência')
            ->assertSee('Praça central')
            ->assertSee('Umuarama, década de 1970.')
            ->assertSee('fetchpriority="high"', false)
            ->assertDontSee('data-hero-controls', false)
            ->assertDontSee('Identidade visual do projeto');
    }

    public function test_multiple_hero_photos_render_navigation_and_hidden_inactive_slide(): void
    {
        config()->set('hero.slides', [
            ['imagem' => 'images/memorias-vivas-logo.jpeg', 'alt' => 'Primeira imagem'],
            ['imagem' => 'images/umuarama-panorama-horizontal.png', 'alt' => 'Segunda imagem'],
        ]);

        $this->get(route('public.home'))
            ->assertOk()
            ->assertSee('aria-roledescription="carrossel"', false)
            ->assertSee('Fotografia anterior')
            ->assertSee('Próxima fotografia')
            ->assertSee('Exibir fotografia 2 de 2')
            ->assertSee('aria-hidden="true"', false)
            ->assertSee('motion-reduce:transition-none', false)
            ->assertSee('data-hero-caption', false)
            ->assertDontSee('Identidade visual do projeto');
    }

    public function test_header_keeps_only_navigation_and_a_search_shortcut(): void
    {
        $html = $this->get(route('public.sobre'))->assertOk()->getContent();
        $header = str($html)->between('aria-label="Cabeçalho público"', '</header>')->toString();

        // Sem campo de busca fora do catálogo: a lupa leva à busca do catálogo.
        $this->assertStringNotContainsString('name="q"', $html);
        $this->assertStringContainsString('href="'.route('public.catalogo').'#busca"', $header);
        $this->assertStringContainsString('aria-label="Pesquisar no acervo"', $header);

        // A área administrativa fica só no rodapé.
        $this->assertStringNotContainsString('Área administrativa', $header);
        $this->assertStringContainsString('Área administrativa', $html);

        $this->assertMatchesRegularExpression(
            '/Início.*Catálogo.*Sobre nós.*Como colaborar.*Contato/s',
            str($header)->between('<nav', '</nav>')->toString(),
        );
    }

    public function test_header_search_shortcut_preserves_the_current_catalog_query(): void
    {
        $this->get(route('public.catalogo', ['q' => 'praça']))
            ->assertOk()
            ->assertSee('href="'.e(route('public.catalogo', ['q' => 'praça'])).'#busca"', false);
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

    public function test_public_home_uses_medium_image_with_safe_fallbacks(): void
    {
        $comTodasAsVersoes = $this->fotografia('Fotografia com todas as versões');
        $thumbnail = $this->arquivo($comTodasAsVersoes, 'thumbnail');
        $medium = $this->arquivo($comTodasAsVersoes, 'medium');
        $large = $this->arquivo($comTodasAsVersoes, 'large');

        $somenteThumbnail = $this->fotografia('Fotografia somente thumbnail');
        $thumbnailFallback = $this->arquivo($somenteThumbnail, 'thumbnail');

        $somenteLarge = $this->fotografia('Fotografia somente large');
        $largeFallback = $this->arquivo($somenteLarge, 'large');

        $this->get(route('public.home'))
            ->assertOk()
            ->assertSee(route('public.imagens.show', $medium), false)
            ->assertDontSee(route('public.imagens.show', $thumbnail), false)
            ->assertDontSee(route('public.imagens.show', $large), false)
            ->assertSee(route('public.imagens.show', $thumbnailFallback), false)
            ->assertSee(route('public.imagens.show', $largeFallback), false);
    }

    public function test_public_home_avoids_redundant_carousel_controls_for_one_photograph(): void
    {
        $fotografia = $this->fotografia('Fotografia única');
        $this->arquivo($fotografia, 'medium');

        $this->get(route('public.home'))
            ->assertOk()
            ->assertSee('data-carousel', false)
            ->assertSee('aria-hidden="false"', false)
            ->assertDontSee('class="public-carousel__control public-carousel__control--prev"', false)
            ->assertDontSee('class="public-carousel__control public-carousel__control--next"', false)
            ->assertDontSee('class="public-carousel__dots"', false)
            ->assertDontSee('class="public-carousel" data-carousel tabindex="0"', false);
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
            ->assertSee('Resultados para “praça”.')
            ->assertSee('Nenhuma fotografia encontrada')
            ->assertSee('Limpar pesquisa');
    }

    public function test_unknown_page_shows_public_navigation_and_recovery_links(): void
    {
        $this
            ->get('/pagina-inexistente')
            ->assertNotFound()
            ->assertSee('Esta página não foi encontrada')
            ->assertSee(route('public.home'), false)
            ->assertSee(route('public.catalogo'), false);
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

    private function arquivo(ItemAcervo $fotografia, string $versao): Arquivo
    {
        return Arquivo::create([
            'item_acervo_id' => $fotografia->id,
            'provider' => 'local',
            'storage_path' => "acervo/{$fotografia->id}/{$versao}.jpg",
            'mime_type' => 'image/jpeg',
            'file_size' => 100,
            'tipo_arquivo' => 'imagem',
            'versao_arquivo' => $versao,
            'width' => $versao === 'thumbnail' ? 320 : 1024,
            'height' => $versao === 'thumbnail' ? 240 : 768,
        ]);
    }
}
