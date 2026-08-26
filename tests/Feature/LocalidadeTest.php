<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\ItemAcervo;
use App\Models\MotivoDownload;
use App\Models\Municipio;
use App\Models\Pais;
use App\Models\RegistroDownload;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LocalidadeTest extends TestCase
{
    use RefreshDatabase;

    private function contexto(): array
    {
        $item = ItemAcervo::create([
            'titulo' => 'Praca central',
            'tipo_item' => 'fotografia',
            'tipo_data' => 'desconhecida',
            'status' => 'publicado',
        ]);

        return [$item, MotivoDownload::create(['titulo' => 'Pesquisa academica'])];
    }

    private function brasil(): Pais
    {
        return Pais::create(['codigo' => 'BR', 'nome' => 'Brasil']);
    }

    public function test_registro_nao_guarda_mais_localizacao_em_texto(): void
    {
        foreach (['pais', 'estado', 'cidade'] as $coluna) {
            $this->assertFalse(Schema::hasColumn('registro_downloads', $coluna), "coluna {$coluna}");
        }

        foreach (['pais_id', 'estado_id', 'municipio_id'] as $coluna) {
            $this->assertTrue(Schema::hasColumn('registro_downloads', $coluna), "coluna {$coluna}");
        }
    }

    public function test_download_do_brasil_guarda_pais_estado_e_municipio(): void
    {
        [$item, $motivo] = $this->contexto();

        $brasil = $this->brasil();
        $parana = Estado::create(['codigo_ibge' => '41', 'sigla' => 'PR', 'nome' => 'Parana']);
        $umuarama = Municipio::create(['codigo_ibge' => '4128104', 'estado_id' => $parana->id, 'nome' => 'Umuarama']);

        $registro = RegistroDownload::create([
            'item_acervo_id' => $item->id,
            'motivo_download_id' => $motivo->id,
            'pais_id' => $brasil->id,
            'estado_id' => $parana->id,
            'municipio_id' => $umuarama->id,
            'created_at' => now(),
        ]);

        $registro->refresh();

        $this->assertTrue($registro->pais->isBrasil());
        $this->assertSame('Parana', $registro->estado->nome);
        $this->assertSame('Umuarama', $registro->municipio->nome);
    }

    public function test_download_de_outro_pais_guarda_somente_o_pais(): void
    {
        [$item, $motivo] = $this->contexto();

        $portugal = Pais::create(['codigo' => 'PT', 'nome' => 'Portugal']);

        $registro = RegistroDownload::create([
            'item_acervo_id' => $item->id,
            'motivo_download_id' => $motivo->id,
            'pais_id' => $portugal->id,
            'created_at' => now(),
        ]);

        $registro->refresh();

        $this->assertFalse($registro->pais->isBrasil());
        $this->assertNull($registro->estado_id);
        $this->assertNull($registro->municipio_id);
    }

    public function test_pais_e_obrigatorio_no_registro(): void
    {
        [$item, $motivo] = $this->contexto();

        $this->expectException(QueryException::class);

        RegistroDownload::create([
            'item_acervo_id' => $item->id,
            'motivo_download_id' => $motivo->id,
            'created_at' => now(),
        ]);
    }

    public function test_municipio_pertence_a_um_estado(): void
    {
        $parana = Estado::create(['codigo_ibge' => '41', 'sigla' => 'PR', 'nome' => 'Parana']);

        Municipio::create(['codigo_ibge' => '4128104', 'estado_id' => $parana->id, 'nome' => 'Umuarama']);
        Municipio::create(['codigo_ibge' => '4106902', 'estado_id' => $parana->id, 'nome' => 'Curitiba']);

        $this->assertCount(2, $parana->municipios);
        $this->assertSame('PR', $parana->municipios->first()->estado->sigla);
    }

    public function test_codigos_de_localidade_sao_unicos(): void
    {
        $this->brasil();

        $this->expectException(QueryException::class);

        Pais::create(['codigo' => 'BR', 'nome' => 'Brasil duplicado']);
    }

    public function test_pais_nasce_ativo(): void
    {
        $this->assertTrue($this->brasil()->refresh()->ativo);
    }

    public function test_localidade_com_download_registrado_nao_pode_ser_apagada(): void
    {
        [$item, $motivo] = $this->contexto();
        $brasil = $this->brasil();

        RegistroDownload::create([
            'item_acervo_id' => $item->id,
            'motivo_download_id' => $motivo->id,
            'pais_id' => $brasil->id,
            'created_at' => now(),
        ]);

        $this->expectException(QueryException::class);

        $brasil->delete();
    }
}
