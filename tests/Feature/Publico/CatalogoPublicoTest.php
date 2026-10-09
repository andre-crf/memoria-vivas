<?php

namespace Tests\Feature\Publico;

use App\Enums\Visibilidade;
use App\Models\Arquivo;
use App\Models\ItemAcervo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CatalogoPublicoTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalogo_exibe_somente_fotografias_elegiveis_com_dados_e_links_publicos(): void
    {
        $elegivel = $this->fotografia([
            'titulo' => 'Praça Miguel Rossafa',
            'tipo_data' => 'ano',
            'ano' => 1985,
        ]);
        $thumbnail = $this->arquivo($elegivel, 'thumbnail');

        $privada = $this->fotografia(['titulo' => 'Fotografia privada', 'visibilidade' => Visibilidade::Privado]);
        $this->arquivo($privada, 'thumbnail');

        $rascunho = $this->fotografia(['titulo' => 'Fotografia em rascunho', 'status' => 'rascunho']);
        $this->arquivo($rascunho, 'thumbnail');

        $documento = $this->fotografia(['titulo' => 'Documento público', 'tipo_item' => 'documento']);
        $this->arquivo($documento, 'thumbnail');

        $semImagem = $this->fotografia(['titulo' => 'Fotografia sem derivação']);

        $excluida = $this->fotografia(['titulo' => 'Fotografia excluída']);
        $this->arquivo($excluida, 'thumbnail');
        $excluida->delete();

        $this->get(route('public.catalogo'))
            ->assertOk()
            ->assertSee('Praça Miguel Rossafa')
            ->assertSee('1985')
            ->assertSee(route('publico.imagens.show', $thumbnail), false)
            ->assertSee(route('public.fotografias.show', $elegivel), false)
            ->assertSee('Imagem indisponível')
            ->assertDontSee('Fotografia privada')
            ->assertDontSee('Fotografia em rascunho')
            ->assertDontSee('Documento público')
            ->assertDontSee('Fotografia sem derivação')
            ->assertDontSee('Fotografia excluída')
            ->assertDontSee($thumbnail->storage_path)
            ->assertDontSee(route('admin.arquivos.show', $thumbnail), false);
    }

    public function test_catalogo_usa_thumbnail_com_fallback_para_medium_e_large(): void
    {
        $comThumbnail = $this->fotografia(['titulo' => 'Com miniatura']);
        $thumbnail = $this->arquivo($comThumbnail, 'thumbnail');
        $this->arquivo($comThumbnail, 'medium');
        $this->arquivo($comThumbnail, 'large');

        $comMedium = $this->fotografia(['titulo' => 'Com média']);
        $medium = $this->arquivo($comMedium, 'medium');
        $this->arquivo($comMedium, 'large');

        $comLarge = $this->fotografia(['titulo' => 'Com grande']);
        $large = $this->arquivo($comLarge, 'large');

        $this->get(route('public.catalogo'))
            ->assertOk()
            ->assertSee(route('publico.imagens.show', $thumbnail), false)
            ->assertSee(route('publico.imagens.show', $medium), false)
            ->assertSee(route('publico.imagens.show', $large), false);
    }

    public function test_catalogo_pagina_doze_registros_em_ordem_deterministica_e_preserva_query_string(): void
    {
        $fotografias = collect(range(1, 13))->map(function (int $indice): ItemAcervo {
            $fotografia = $this->fotografia([
                'titulo' => sprintf('Fotografia %02d', $indice),
            ]);
            $this->arquivo($fotografia, 'thumbnail');

            return $fotografia;
        });

        DB::table('item_acervos')->whereIn('id', $fotografias->pluck('id'))->update([
            'created_at' => now(),
        ]);

        $this->get(route('public.catalogo', ['q' => 'memória']))
            ->assertOk()
            ->assertViewHas('fotografias', fn ($paginador): bool => $paginador->count() === 12
                && $paginador->total() === 13
                && $paginador->currentPage() === 1)
            ->assertSeeInOrder([
                'Fotografia 13',
                'Fotografia 12',
                'Fotografia 11',
                'Fotografia 10',
            ])
            ->assertSee('Fotografia 13')
            ->assertSee('Fotografia 02')
            ->assertDontSee('Fotografia 01')
            ->assertSee('q=mem%C3%B3ria', false)
            ->assertSee('page=2', false);

        $this->get(route('public.catalogo', ['q' => 'memória', 'page' => 2]))
            ->assertOk()
            ->assertViewHas('fotografias', fn ($paginador): bool => $paginador->count() === 1
                && $paginador->currentPage() === 2)
            ->assertSee('Fotografia 01')
            ->assertDontSee('Fotografia 02');
    }

    public function test_busca_ainda_nao_filtra_o_catalogo(): void
    {
        $primeira = $this->fotografia(['titulo' => 'Praça central']);
        $this->arquivo($primeira, 'thumbnail');
        $segunda = $this->fotografia(['titulo' => 'Estação rodoviária']);
        $this->arquivo($segunda, 'thumbnail');

        $this->get(route('public.catalogo', ['q' => 'praça']))
            ->assertOk()
            ->assertSee('A pesquisa por “praça” será disponibilizada em uma próxima etapa.')
            ->assertSee('Praça central')
            ->assertSee('Estação rodoviária');
    }

    public function test_catalogo_exibe_estado_vazio_sem_fotografias_elegiveis(): void
    {
        $this->get(route('public.catalogo'))
            ->assertOk()
            ->assertSee('Nenhuma fotografia pública disponível')
            ->assertSee('Novos registros aparecerão aqui');
    }

    public function test_detalhe_minimo_exibe_maior_derivacao_titulo_data_e_retorno(): void
    {
        $fotografia = $this->fotografia([
            'titulo' => 'Avenida Paraná',
            'tipo_data' => 'decada',
            'decada' => '1970',
        ]);
        $thumbnail = $this->arquivo($fotografia, 'thumbnail');
        $medium = $this->arquivo($fotografia, 'medium');
        $large = $this->arquivo($fotografia, 'large');

        $this->get(route('public.fotografias.show', $fotografia))
            ->assertOk()
            ->assertSee('Avenida Paraná')
            ->assertSee('Década de 1970')
            ->assertSee(route('publico.imagens.show', $large), false)
            ->assertDontSee(route('publico.imagens.show', $medium), false)
            ->assertDontSee(route('publico.imagens.show', $thumbnail), false)
            ->assertSee(route('public.catalogo'), false)
            ->assertSee('Imagem indisponível');
    }

    public function test_detalhe_rejeita_identificador_inexistente_ou_fotografia_inelegivel(): void
    {
        $casos = [
            ['status' => 'rascunho'],
            ['status' => 'em_revisao'],
            ['status' => 'arquivado'],
            ['visibilidade' => Visibilidade::Privado],
            ['tipo_item' => 'documento'],
        ];

        foreach ($casos as $atributos) {
            $fotografia = $this->fotografia($atributos);
            $this->arquivo($fotografia, 'large');

            $this->get(route('public.fotografias.show', $fotografia))->assertNotFound();
        }

        $semImagem = $this->fotografia();
        $this->get(route('public.fotografias.show', $semImagem))->assertNotFound();

        $excluida = $this->fotografia();
        $this->arquivo($excluida, 'large');
        $excluida->delete();
        $this->get(route('public.fotografias.show', $excluida->id))->assertNotFound();

        $this->get(route('public.fotografias.show', 999999))->assertNotFound();
    }

    public function test_usuario_autenticado_acessa_catalogo_e_detalhe_publicos(): void
    {
        $fotografia = $this->fotografia();
        $this->arquivo($fotografia, 'thumbnail');

        $this->actingAs(User::factory()->create())
            ->get(route('public.catalogo'))
            ->assertOk();

        $this->get(route('public.fotografias.show', $fotografia))->assertOk();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function fotografia(array $attributes = []): ItemAcervo
    {
        return ItemAcervo::create([
            'titulo' => 'Fotografia '.uniqid(),
            'tipo_item' => 'fotografia',
            'tipo_data' => 'desconhecida',
            'status' => 'publicado',
            'visibilidade' => Visibilidade::Publico,
            ...$attributes,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function arquivo(ItemAcervo $fotografia, string $versao, array $attributes = []): Arquivo
    {
        return Arquivo::create([
            'item_acervo_id' => $fotografia->id,
            'nome_original' => null,
            'provider' => 'local',
            'storage_path' => "acervo/derivados/{$fotografia->id}/{$versao}.jpg",
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'tipo_arquivo' => 'imagem',
            'versao_arquivo' => $versao,
            'width' => 800,
            'height' => 600,
            ...$attributes,
        ]);
    }
}
