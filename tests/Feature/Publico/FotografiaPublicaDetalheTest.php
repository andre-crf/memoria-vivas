<?php

namespace Tests\Feature\Publico;

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
use App\Queries\Publico\ConsultaFotografiasPublicas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FotografiaPublicaDetalheTest extends TestCase
{
    use RefreshDatabase;

    public function test_detalhe_exibe_informacoes_historicas_e_relacoes_publicas_sem_dados_internos(): void
    {
        $autor = Autor::create([
            'nome' => 'Arquivo Histórico Municipal',
            'tipo' => 'instituicao',
            'observacao' => 'Observação interna do autor',
        ]);
        $fotografia = $this->fotografia([
            'titulo' => 'Desfile na Avenida Paraná',
            'legenda' => 'Registro do desfile comemorativo.',
            'tipo_data' => 'data_exata',
            'dia' => 25,
            'mes' => 9,
            'ano' => 1985,
            'local_atual' => 'Avenida Paraná',
            'local_epoca' => 'Rua Principal',
            'evento' => 'Aniversário de Umuarama',
            'cedente' => 'Família Oliveira',
            'estado_conservacao' => 'critico',
            'autor_id' => $autor->id,
        ]);
        $derivacao = $this->arquivo($fotografia, 'large');
        Arquivo::create([
            'item_acervo_id' => $fotografia->id,
            'nome_original' => 'original-nao-publico.tiff',
            'provider' => 'local',
            'storage_path' => 'acervo/caminho-interno-original.tiff',
            'mime_type' => 'image/tiff',
            'file_size' => 4096,
            'tipo_arquivo' => 'imagem',
            'versao_arquivo' => 'original',
        ]);

        $categoriaZ = Categoria::create(['titulo' => 'Zona urbana', 'descricao' => 'Descrição interna da categoria']);
        $categoriaA = Categoria::create(['titulo' => 'Arquitetura']);
        $assuntoZ = Assunto::create(['titulo' => 'Vida cotidiana']);
        $assuntoA = Assunto::create(['titulo' => 'Celebrações']);
        $palavraZ = PalavraChave::create(['termo' => 'Umuarama']);
        $palavraA = PalavraChave::create(['termo' => 'avenida']);
        $pessoaZ = Pessoa::create(['nome' => 'Zilda Souza', 'observacao' => 'Observação interna da pessoa']);
        $pessoaA = Pessoa::create(['nome' => 'Ana Souza']);
        $colecaoZ = Colecao::create(['titulo' => 'Umuarama ontem', 'status' => 'publicada']);
        $colecaoA = Colecao::create(['titulo' => 'Aniversários da cidade', 'status' => 'publicada']);
        $colecaoRascunho = Colecao::create(['titulo' => 'Coleção secreta em preparação', 'status' => 'rascunho']);
        $colecaoArquivada = Colecao::create(['titulo' => 'Coleção pública arquivada', 'status' => 'arquivada']);
        $contextoOrdemDois = ConjuntoContextual::create(['titulo' => 'Abertura do desfile']);
        $contextoOrdemUm = ConjuntoContextual::create(['titulo' => 'Encerramento do desfile']);

        $fotografia->categorias()->attach([$categoriaZ->id, $categoriaA->id]);
        $fotografia->assuntos()->attach([$assuntoZ->id, $assuntoA->id]);
        $fotografia->palavrasChave()->attach([$palavraZ->id, $palavraA->id]);
        $fotografia->pessoas()->attach([$pessoaZ->id, $pessoaA->id]);
        $fotografia->colecoes()->attach([
            $colecaoZ->id,
            $colecaoA->id,
            $colecaoRascunho->id,
            $colecaoArquivada->id,
        ]);
        $fotografia->conjuntosContextuais()->attach([
            $contextoOrdemDois->id => ['ordem' => 2],
            $contextoOrdemUm->id => ['ordem' => 1],
        ]);

        $response = $this->get(route('public.fotografias.show', $fotografia));

        $response
            ->assertOk()
            ->assertSee('Desfile na Avenida Paraná')
            ->assertSee('Registro do desfile comemorativo.')
            ->assertSee('25/09/1985')
            ->assertSee('Data exata')
            ->assertSee('Avenida Paraná')
            ->assertSee('Rua Principal')
            ->assertSee('Aniversário de Umuarama')
            ->assertSee('Família Oliveira')
            ->assertSee('Arquivo Histórico Municipal')
            ->assertSee(route('publico.imagens.show', $derivacao), false)
            ->assertSeeInOrder(['Arquitetura', 'Zona urbana'])
            ->assertSeeInOrder(['Celebrações', 'Vida cotidiana'])
            ->assertSeeInOrder(['avenida', 'Umuarama'])
            ->assertSeeInOrder(['Ana Souza', 'Zilda Souza'])
            ->assertSeeInOrder(['Aniversários da cidade', 'Umuarama ontem'])
            ->assertSeeInOrder(['Encerramento do desfile', 'Abertura do desfile'])
            ->assertDontSee('Coleção secreta em preparação')
            ->assertDontSee('Coleção pública arquivada')
            ->assertDontSee('Observação interna do autor')
            ->assertDontSee('Observação interna da pessoa')
            ->assertDontSee('Descrição interna da categoria')
            ->assertDontSee('Crítico')
            ->assertDontSee('original-nao-publico.tiff')
            ->assertDontSee('acervo/caminho-interno-original.tiff');
    }

    public function test_consulta_de_detalhe_carrega_relacoes_sem_alterar_consulta_enxuta_por_identificador(): void
    {
        $fotografia = $this->fotografia();
        $this->arquivo($fotografia, 'thumbnail');
        $fotografia->categorias()->attach(Categoria::create(['titulo' => 'Memória urbana']));
        $fotografia->colecoes()->attach(Colecao::create(['titulo' => 'Coleção pública', 'status' => 'publicada']));

        $consulta = app(ConsultaFotografiasPublicas::class);
        $resultadoEnxuto = $consulta->porIdentificador($fotografia->id);
        $resultadoDetalhado = $consulta->detalhePorIdentificador($fotografia->id);

        $this->assertNotNull($resultadoEnxuto);
        $this->assertTrue($resultadoEnxuto->relationLoaded('arquivos'));
        $this->assertFalse($resultadoEnxuto->relationLoaded('categorias'));
        $this->assertNotNull($resultadoDetalhado);

        foreach (['arquivos', 'autor', 'categorias', 'assuntos', 'palavrasChave', 'pessoas', 'colecoes', 'conjuntosContextuais'] as $relacao) {
            $this->assertTrue($resultadoDetalhado->relationLoaded($relacao), "A relação {$relacao} deveria estar pré-carregada.");
        }
    }

    public function test_detalhe_omite_secoes_sem_relacionamentos_e_campos_opcionais(): void
    {
        $fotografia = $this->fotografia();
        $this->arquivo($fotografia, 'medium');

        $this->get(route('public.fotografias.show', $fotografia))
            ->assertOk()
            ->assertSee('Data desconhecida')
            ->assertSee('Precisão da data')
            ->assertSee('Desconhecida')
            ->assertDontSee('Classificações')
            ->assertDontSee('Pessoas identificadas')
            ->assertDontSee('Coleções e contextos')
            ->assertDontSee('Local atual')
            ->assertDontSee('Local na época')
            ->assertDontSee('Evento')
            ->assertDontSee('Cedente')
            ->assertDontSee('Autoria');
    }

    public function test_catalogo_e_detalhe_preservam_somente_o_contexto_normalizado_de_retorno(): void
    {
        $categoria = Categoria::create(['titulo' => 'Paisagem urbana']);
        $assunto = Assunto::create(['titulo' => 'Centro']);
        $fotografia = $this->fotografia([
            'titulo' => 'Memória da avenida',
            'tipo_data' => 'ano',
            'ano' => 1985,
        ]);
        $this->arquivo($fotografia, 'thumbnail');
        $fotografia->categorias()->attach($categoria);
        $fotografia->assuntos()->attach($assunto);

        $contexto = [
            'q' => 'Memória',
            'decada' => '1980',
            'categoria' => $categoria->id,
            'assunto' => $assunto->id,
            'page' => 1,
        ];
        $urlDetalhe = route('public.fotografias.show', [
            'fotografia' => $fotografia,
            ...$contexto,
        ]);

        $this->get(route('public.catalogo', $contexto))
            ->assertOk()
            ->assertSee($urlDetalhe);

        $this->get($urlDetalhe)
            ->assertOk()
            ->assertSee(route('public.catalogo', $contexto));

        $this->get(route('public.fotografias.show', [
            'fotografia' => $fotografia,
            'q' => ['inválido'],
            'decada' => '1985',
            'categoria' => '-1',
            'assunto' => 'texto',
            'page' => '0',
            'retorno' => 'https://example.com/externo',
        ]))
            ->assertOk()
            ->assertSee('href="'.route('public.catalogo').'"', false)
            ->assertDontSee('example.com/externo');
    }

    /** @param array<string, mixed> $attributes */
    private function fotografia(array $attributes = []): ItemAcervo
    {
        return ItemAcervo::create([
            'titulo' => 'Fotografia pública',
            'tipo_item' => 'fotografia',
            'tipo_data' => 'desconhecida',
            'status' => 'publicado',
            'visibilidade' => Visibilidade::Publico,
            ...$attributes,
        ]);
    }

    private function arquivo(ItemAcervo $fotografia, string $versao): Arquivo
    {
        return Arquivo::create([
            'item_acervo_id' => $fotografia->id,
            'provider' => 'local',
            'storage_path' => "acervo/derivados/{$fotografia->id}/{$versao}.jpg",
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'tipo_arquivo' => 'imagem',
            'versao_arquivo' => $versao,
            'width' => 1200,
            'height' => 800,
        ]);
    }
}
