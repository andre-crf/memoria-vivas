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

        $this->get(route('public.catalogo', ['q' => 'Fotografia']))
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
            ->assertSee('q=Fotografia', false)
            ->assertSee('page=2', false);

        $this->get(route('public.catalogo', ['q' => 'Fotografia', 'page' => 2]))
            ->assertOk()
            ->assertViewHas('fotografias', fn ($paginador): bool => $paginador->count() === 1
                && $paginador->currentPage() === 2)
            ->assertSee('Fotografia 01')
            ->assertDontSee('Fotografia 02');
    }

    public function test_busca_filtra_por_frase_parcial_no_titulo_ou_na_legenda(): void
    {
        $porTitulo = $this->fotografia(['titulo' => 'Praça Central de Umuarama']);
        $this->arquivo($porTitulo, 'thumbnail');
        $porLegenda = $this->fotografia([
            'titulo' => 'Encontro comunitário',
            'legenda' => 'Moradores reunidos na Praça Central em 1980.',
        ]);
        $this->arquivo($porLegenda, 'thumbnail');
        $naoCorrespondente = $this->fotografia([
            'titulo' => 'Estação rodoviária',
            'legenda' => 'Ônibus estacionados no terminal.',
        ]);
        $this->arquivo($naoCorrespondente, 'thumbnail');

        $this->get(route('public.catalogo', ['q' => 'Praça Central']))
            ->assertOk()
            ->assertSee('Resultados para “Praça Central”.')
            ->assertSee('Praça Central de Umuarama')
            ->assertSee('Encontro comunitário')
            ->assertDontSee('Estação rodoviária');
    }

    public function test_busca_nao_diferencia_maiusculas_de_minusculas_e_mantem_acentos_armazenados(): void
    {
        $fotografia = $this->fotografia(['titulo' => 'Memória Urbana']);
        $this->arquivo($fotografia, 'thumbnail');

        $this->get(route('public.catalogo', ['q' => 'memória urbana']))
            ->assertOk()
            ->assertSee('Memória Urbana');
    }

    public function test_busca_trata_frase_com_varias_palavras_como_uma_unica_sequencia(): void
    {
        $sequencia = $this->fotografia(['titulo' => 'Praça Central de Umuarama']);
        $this->arquivo($sequencia, 'thumbnail');
        $palavrasSeparadas = $this->fotografia(['titulo' => 'Praça histórica de Umuarama Central']);
        $this->arquivo($palavrasSeparadas, 'thumbnail');

        $this->get(route('public.catalogo', ['q' => 'Praça Central']))
            ->assertOk()
            ->assertSee('Praça Central de Umuarama')
            ->assertDontSee('Praça histórica de Umuarama Central');
    }

    public function test_busca_trata_curingas_e_caractere_de_escape_como_literais(): void
    {
        foreach ([
            ['titulo' => 'Celebração 100%', 'q' => '%', 'ausente' => 'Celebração 100 anos'],
            ['titulo' => 'Coleção_A', 'q' => '_', 'ausente' => 'ColeçãoXA'],
            ['titulo' => 'Memória! Viva', 'q' => '!', 'ausente' => 'Memória Viva'],
        ] as $caso) {
            $correspondente = $this->fotografia(['titulo' => $caso['titulo']]);
            $this->arquivo($correspondente, 'thumbnail');
            $outro = $this->fotografia(['titulo' => $caso['ausente']]);
            $this->arquivo($outro, 'thumbnail');

            $this->get(route('public.catalogo', ['q' => $caso['q']]))
                ->assertOk()
                ->assertSee($caso['titulo'])
                ->assertDontSee($caso['ausente']);

            $correspondente->arquivos()->delete();
            $correspondente->delete();
            $outro->arquivos()->delete();
            $outro->delete();
        }
    }

    public function test_busca_mantem_as_regras_de_elegibilidade_publica(): void
    {
        $publica = $this->fotografia(['titulo' => 'Memória pesquisável pública']);
        $this->arquivo($publica, 'thumbnail');

        $privada = $this->fotografia([
            'titulo' => 'Memória pesquisável privada',
            'visibilidade' => Visibilidade::Privado,
        ]);
        $this->arquivo($privada, 'thumbnail');

        $rascunho = $this->fotografia([
            'titulo' => 'Memória pesquisável em rascunho',
            'status' => 'rascunho',
        ]);
        $this->arquivo($rascunho, 'thumbnail');

        $excluida = $this->fotografia(['titulo' => 'Memória pesquisável excluída']);
        $this->arquivo($excluida, 'thumbnail');
        $excluida->delete();

        $this->get(route('public.catalogo', ['q' => 'Memória pesquisável']))
            ->assertOk()
            ->assertSee('Memória pesquisável pública')
            ->assertDontSee('Memória pesquisável privada')
            ->assertDontSee('Memória pesquisável em rascunho')
            ->assertDontSee('Memória pesquisável excluída');
    }

    public function test_busca_vazia_ou_composta_por_espacos_retorna_o_catalogo_completo(): void
    {
        $fotografia = $this->fotografia(['titulo' => 'Registro disponível']);
        $this->arquivo($fotografia, 'thumbnail');

        $this->get(route('public.catalogo', ['q' => '   ']))
            ->assertOk()
            ->assertSee('Registro disponível')
            ->assertDontSee('Resultados para')
            ->assertDontSee('Nenhuma fotografia encontrada');
    }

    public function test_busca_sem_resultados_exibe_estado_especifico_e_link_para_limpar(): void
    {
        $fotografia = $this->fotografia(['titulo' => 'Avenida Paraná']);
        $this->arquivo($fotografia, 'thumbnail');

        $this->get(route('public.catalogo', ['q' => 'resultado inexistente']))
            ->assertOk()
            ->assertSee('Nenhuma fotografia encontrada')
            ->assertSee('Não encontramos resultados para “resultado inexistente”.')
            ->assertSee('Limpar pesquisa')
            ->assertSee('href="'.route('public.catalogo').'"', false)
            ->assertDontSee('Nenhuma fotografia pública disponível');
    }

    public function test_campo_de_busca_preserva_termo_normalizado_e_ignora_parametro_nao_escalar(): void
    {
        $fotografia = $this->fotografia(['titulo' => 'Praça Central']);
        $this->arquivo($fotografia, 'thumbnail');

        $this->get(route('public.catalogo', ['q' => '  Praça  ']))
            ->assertOk()
            ->assertSee('value="Praça"', false)
            ->assertSee('Praça Central');

        $this->get(route('public.catalogo', ['q' => ['inválido']]))
            ->assertOk()
            ->assertSee('value=""', false)
            ->assertSee('Praça Central');
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
