<?php

namespace Database\Seeders;

use App\Models\Estado;
use App\Models\Municipio;
use App\Models\Pais;
use Illuminate\Database\Seeder;

/**
 * Localidades mínimas para os testes de navegador do formulário de download.
 * Em produção, as tabelas vêm do IBGE (php artisan localidades:sync).
 */
class E2eLocalidadesSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['BR' => 'Brasil', 'AR' => 'Argentina', 'PT' => 'Portugal'] as $codigo => $nome) {
            Pais::updateOrCreate(['codigo' => $codigo], ['nome' => $nome]);
        }

        $estados = [
            ['41', 'PR', 'Paraná', [['4128104', 'Umuarama'], ['4106902', 'Curitiba']]],
            ['35', 'SP', 'São Paulo', [['3509502', 'Campinas']]],
        ];
        foreach ($estados as [$codigoIbge, $sigla, $nome, $municipios]) {
            $estado = Estado::updateOrCreate(['codigo_ibge' => $codigoIbge], ['sigla' => $sigla, 'nome' => $nome]);

            foreach ($municipios as [$codigoMunicipio, $nomeMunicipio]) {
                Municipio::updateOrCreate(['codigo_ibge' => $codigoMunicipio], ['estado_id' => $estado->id, 'nome' => $nomeMunicipio]);
            }
        }
    }
}
