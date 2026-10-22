<?php

namespace Tests\Feature\Publico;

use App\Models\Arquivo;
use App\Models\ItemAcervo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogoTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_lists_public_photographs_with_derived_images_and_detail_links(): void
    {
        $item = $this->photograph(['titulo' => 'Praça central']);
        $thumbnail = $item->arquivoDaVersao('thumbnail');
        $original = $item->arquivos()->create([
            'provider' => 'local', 'storage_path' => 'original-privado.jpg',
            'mime_type' => 'image/jpeg', 'tipo_arquivo' => 'imagem',
            'versao_arquivo' => 'original', 'nome_original' => 'original-privado.jpg', 'file_size' => 100,
        ]);

        $this->get(route('public.catalogo'))->assertOk()
            ->assertSee('Praça central')
            ->assertSee(route('public.imagens.show', $thumbnail), false)
            ->assertSee(route('public.fotografias.show', $item->id), false)
            ->assertDontSee(route('public.imagens.show', $original), false)
            ->assertDontSee('Nenhum item público disponível ainda');

        $this->get(route('public.fotografias.show', $item->id))->assertOk()
            ->assertSee('Praça central')->assertSee('Voltar ao catálogo')
            ->assertDontSee('original-privado.jpg');
    }

    public function test_catalog_and_detail_exclude_ineligible_photographs(): void
    {
        foreach ([['status' => 'rascunho'], ['status' => 'em_revisao'], ['status' => 'arquivado'], ['visibilidade' => 'privado'], ['tipo_item' => 'documento']] as $attributes) {
            $item = $this->photograph(['titulo' => 'Oculta '.$attributes[array_key_first($attributes)], ...$attributes]);
            $this->get(route('public.catalogo'))->assertOk()->assertDontSee($item->titulo);
            $this->get(route('public.fotografias.show', $item->id))->assertNotFound();
        }

        $deleted = $this->photograph(['titulo' => 'Fotografia removida']);
        $deleted->delete();
        $this->get(route('public.catalogo'))->assertDontSee('Fotografia removida');
        $this->get(route('public.fotografias.show', $deleted->id))->assertNotFound();
        $this->get(route('public.fotografias.show', 999999))->assertNotFound();

        $withoutImage = $this->photograph(['titulo' => 'Sem derivação']);
        $withoutImage->arquivos()->delete();
        $this->get(route('public.catalogo'))->assertDontSee('Sem derivação');
        $this->get(route('public.fotografias.show', $withoutImage->id))->assertNotFound();
    }

    public function test_search_filters_title_caption_and_locations_and_treats_wildcards_as_text(): void
    {
        $this->photograph(['titulo' => 'Praça central']);
        $this->photograph(['titulo' => 'Retrato', 'legenda' => 'Praça ao fundo']);
        $this->photograph(['titulo' => 'Paisagem', 'local_epoca' => 'Praça antiga']);
        $this->photograph(['titulo' => 'Evento', 'local_atual' => 'Praça nova']);
        $this->photograph(['titulo' => 'Outra fotografia']);
        $this->get(route('public.catalogo', ['q' => 'Praça']))->assertOk()
            ->assertSee('Praça central')->assertSee('Retrato')->assertSee('Paisagem')->assertSee('Evento')
            ->assertDontSee('Outra fotografia');

        $this->photograph(['titulo' => '100% preservado']);
        $this->get(route('public.catalogo', ['q' => '%']))->assertOk()
            ->assertSee('100% preservado')->assertDontSee('Outra fotografia');
        $this->get(route('public.catalogo', ['q' => 'inexistente']))->assertOk()
            ->assertSee('Nenhuma fotografia encontrada para esta busca')->assertSee('Limpar pesquisa');
    }

    public function test_pagination_limits_results_and_preserves_search(): void
    {
        $oldest = $this->photograph(['titulo' => 'Registro mais antigo']);
        foreach (range(1, 12) as $number) {
            $this->photograph(['titulo' => 'Registro recente '.$number]);
        }

        $this->get(route('public.catalogo', ['q' => 'Registro']))->assertOk()
            ->assertViewHas('fotografias', fn ($items) => $items->count() === 12 && $items->total() === 13)
            ->assertDontSee($oldest->titulo)->assertSee('q=Registro', false);
        $this->get(route('public.catalogo', ['q' => 'Registro', 'page' => 2]))->assertOk()
            ->assertSee($oldest->titulo)->assertDontSee('Registro recente 12');
    }

    private function photograph(array $attributes = []): ItemAcervo
    {
        $item = ItemAcervo::create([
            'tipo_item' => 'fotografia', 'titulo' => 'Fotografia pública',
            'tipo_data' => 'desconhecida', 'status' => 'publicado', 'visibilidade' => 'publico',
            ...$attributes,
        ]);
        Arquivo::create([
            'item_acervo_id' => $item->id, 'provider' => 'local',
            'storage_path' => "acervo/{$item->id}/thumbnail.jpg", 'mime_type' => 'image/jpeg',
            'tipo_arquivo' => 'imagem', 'versao_arquivo' => 'thumbnail', 'file_size' => 100,
        ]);

        return $item;
    }
}
