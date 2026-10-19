<?php

namespace Tests\Feature;

use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_local_navigation_links_and_fragment_targets_are_accessible_to_guests(): void
    {
        foreach (['public.home', 'public.catalogo'] as $route) {
            $response = $this->get(route($route))->assertOk();
            $document = new DOMDocument;
            @$document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
            $xpath = new DOMXPath($document);

            foreach ($xpath->query('//a[@href]') as $link) {
                $href = $link->getAttribute('href');
                $url = parse_url($href);

                if (isset($url['host']) && $url['host'] !== parse_url(config('app.url'), PHP_URL_HOST)) {
                    continue;
                }

                $destination = $this->get($url['path'] ?? route($route))->assertOk();

                if (isset($url['fragment'])) {
                    $targetDocument = new DOMDocument;
                    @$targetDocument->loadHTML($destination->getContent());
                    $targets = (new DOMXPath($targetDocument))->query('//*[@id="'.$url['fragment'].'"]');
                    $this->assertSame(1, $targets->length, "Destino ausente: {$href}");
                }
            }
        }
    }

    public function test_header_search_keeps_normalized_term_and_active_catalog_navigation(): void
    {
        $this->get(route('public.catalogo', ['q' => '  praça  ']))
            ->assertOk()
            ->assertSee('Busca por “praça”.')
            ->assertSee('value="praça"', false)
            ->assertSee('aria-current="page"', false)
            ->assertSee('Nenhuma fotografia encontrada para esta busca');
    }

    public function test_search_rejects_array_and_oversized_terms_without_server_errors(): void
    {
        foreach ([['indevido'], str_repeat('a', 201)] as $term) {
            $this->get(route('public.catalogo', ['q' => $term]))
                ->assertStatus(422)
                ->assertSee('catalog-search-error', false)
                ->assertSee('aria-invalid="true"', false);
        }
    }

    public function test_validation_errors_are_displayed_on_the_search_form(): void
    {
        $this->get(route('public.catalogo', ['q' => ['inválido']]))
            ->assertStatus(422)
            ->assertSee('Digite um texto válido para pesquisar no catálogo.')
            ->assertSee('aria-describedby="catalog-search-error"', false);
    }

    public function test_search_text_is_escaped_in_results_and_header(): void
    {
        $this->get(route('public.catalogo', ['q' => '<script>alert(1)</script>']))
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_empty_search_preserves_catalog_empty_state(): void
    {
        $this->get(route('public.catalogo', ['q' => '  ']))
            ->assertOk()
            ->assertDontSee('Busca por')
            ->assertSee('Nenhum item público disponível ainda');
    }
}
