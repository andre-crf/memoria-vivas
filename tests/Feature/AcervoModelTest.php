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
use App\Models\MotivoDownload;
use App\Models\PalavraChave;
use App\Models\PerfilDownload;
use App\Models\Pessoa;
use App\Models\RegistroDownload;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcervoModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_item_acervo_relations_match_catalog_schema(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $autor = Autor::create(['nome' => 'Fundacao Cultural', 'tipo' => 'instituicao']);

        $item = ItemAcervo::create([
            'titulo' => 'Praca central',
            'tipo_item' => 'fotografia',
            'tipo_data' => 'ano',
            'ano' => 1980,
            'status' => 'publicado',
            'visibilidade' => Visibilidade::Publico,
            'autor_id' => $autor->id,
        ]);

        $categoria = Categoria::create(['titulo' => 'Fotografia']);
        $assunto = Assunto::create(['titulo' => 'Espaco urbano']);
        $palavraChave = PalavraChave::create(['termo' => 'centro']);
        $pessoa = Pessoa::create(['nome' => 'Morador identificado']);
        $colecao = Colecao::create(['titulo' => 'Umuarama nos anos 80']);
        $conjunto = ConjuntoContextual::create(['titulo' => 'Centro historico']);

        $item->categorias()->attach($categoria);
        $item->assuntos()->attach($assunto);
        $item->palavrasChave()->attach($palavraChave);
        $item->pessoas()->attach($pessoa);
        $item->colecoes()->attach($colecao);
        $item->conjuntosContextuais()->attach($conjunto, ['ordem' => 1]);

        Arquivo::create([
            'item_acervo_id' => $item->id,
            'nome_original' => 'praca.jpg',
            'provider' => 'google_drive',
            'storage_path' => 'acervo/praca.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'tipo_arquivo' => 'imagem',
            'versao_arquivo' => 'original',
            'width' => 1200,
            'height' => 800,
        ]);

        $motivo = MotivoDownload::create(['titulo' => 'Pesquisa academica']);
        $perfil = PerfilDownload::create(['titulo' => 'Estudante']);

        RegistroDownload::create([
            'item_acervo_id' => $item->id,
            'motivo_download_id' => $motivo->id,
            'perfil_download_id' => $perfil->id,
            'pais' => 'Brasil',
            'estado' => 'PR',
            'cidade' => 'Umuarama',
            'created_at' => now(),
        ]);

        $item->refresh();

        $this->assertTrue($item->podeSerExibidoPublicamente());
        $this->assertSame('Fundacao Cultural', $item->autor->nome);
        $this->assertSame('praca.jpg', $item->arquivos->first()->nome_original);
        $this->assertTrue($item->arquivos->first()->isOriginal());
        $this->assertSame('Fotografia', $item->categorias->first()->titulo);
        $this->assertSame('Espaco urbano', $item->assuntos->first()->titulo);
        $this->assertSame('centro', $item->palavrasChave->first()->termo);
        $this->assertSame('Morador identificado', $item->pessoas->first()->nome);
        $this->assertSame('Umuarama nos anos 80', $item->colecoes->first()->titulo);
        $this->assertSame(1, $item->conjuntosContextuais->first()->pivot->ordem);
        $this->assertSame('Pesquisa academica', $item->registroDownloads->first()->motivoDownload->titulo);
        $this->assertSame(Visibilidade::Publico, $item->visibilidade);
        $this->assertTrue($item->isPublico());
        $this->assertSame($user->id, $item->criadoPor->id);
        $this->assertSame($user->id, $item->atualizadoPor->id);
    }

    public function test_item_acervo_nasce_privado_por_padrao(): void
    {
        $item = ItemAcervo::create([
            'titulo' => 'Documento sem visibilidade definida',
            'tipo_item' => 'documento',
            'tipo_data' => 'desconhecida',
            'status' => 'publicado',
        ]);

        $item->refresh();

        $this->assertSame(Visibilidade::Privado, $item->visibilidade);
        $this->assertFalse($item->isPublico());
        $this->assertFalse($item->podeSerExibidoPublicamente());
    }
}
