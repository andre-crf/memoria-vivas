<?php

namespace Tests\Unit;

use App\Enums\TipoData;
use App\Support\DataHistorica;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DataHistoricaTest extends TestCase
{
    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function combinacoesValidas(): array
    {
        return [
            'data exata' => [['tipo_data' => 'data_exata', 'dia' => 29, 'mes' => 2, 'ano' => 1980]],
            'mes e ano' => [['tipo_data' => 'mes_ano', 'mes' => 12, 'ano' => 1980]],
            'somente ano' => [['tipo_data' => 'ano', 'ano' => 1980]],
            'decada' => [['tipo_data' => 'decada', 'decada' => '1980']],
            'desconhecida' => [['tipo_data' => 'desconhecida']],
            'strings vazias contam como vazio' => [
                ['tipo_data' => 'ano', 'ano' => 1980, 'dia' => '', 'mes' => '', 'decada' => ''],
            ],
        ];
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function combinacoesInvalidas(): array
    {
        return [
            // data_exata exige dia, mes e ano e proíbe decada
            'data exata sem dia' => [['tipo_data' => 'data_exata', 'mes' => 2, 'ano' => 1980], 'dia'],
            'data exata sem mes' => [['tipo_data' => 'data_exata', 'dia' => 1, 'ano' => 1980], 'mes'],
            'data exata sem ano' => [['tipo_data' => 'data_exata', 'dia' => 1, 'mes' => 2], 'ano'],
            'data exata com decada' => [
                ['tipo_data' => 'data_exata', 'dia' => 1, 'mes' => 2, 'ano' => 1980, 'decada' => '1980'], 'decada',
            ],

            // mes_ano exige mes e ano e proíbe dia e decada
            'mes ano sem mes' => [['tipo_data' => 'mes_ano', 'ano' => 1980], 'mes'],
            'mes ano sem ano' => [['tipo_data' => 'mes_ano', 'mes' => 2], 'ano'],
            'mes ano com dia' => [['tipo_data' => 'mes_ano', 'dia' => 1, 'mes' => 2, 'ano' => 1980], 'dia'],
            'mes ano com decada' => [['tipo_data' => 'mes_ano', 'mes' => 2, 'ano' => 1980, 'decada' => '1980'], 'decada'],

            // ano exige ano e proíbe dia, mes e decada
            'ano sem ano' => [['tipo_data' => 'ano'], 'ano'],
            'ano com dia' => [['tipo_data' => 'ano', 'dia' => 1, 'ano' => 1980], 'dia'],
            'ano com mes' => [['tipo_data' => 'ano', 'mes' => 2, 'ano' => 1980], 'mes'],
            'ano com decada' => [['tipo_data' => 'ano', 'ano' => 1980, 'decada' => '1980'], 'decada'],

            // decada exige decada e proíbe dia, mes e ano
            'decada sem decada' => [['tipo_data' => 'decada'], 'decada'],
            'decada com dia' => [['tipo_data' => 'decada', 'dia' => 1, 'decada' => '1980'], 'dia'],
            'decada com mes' => [['tipo_data' => 'decada', 'mes' => 2, 'decada' => '1980'], 'mes'],
            'decada com ano' => [['tipo_data' => 'decada', 'ano' => 1980, 'decada' => '1980'], 'ano'],
            'decada fora do formato' => [['tipo_data' => 'decada', 'decada' => '1985'], 'decada'],

            // desconhecida proíbe todos
            'desconhecida com dia' => [['tipo_data' => 'desconhecida', 'dia' => 1], 'dia'],
            'desconhecida com mes' => [['tipo_data' => 'desconhecida', 'mes' => 2], 'mes'],
            'desconhecida com ano' => [['tipo_data' => 'desconhecida', 'ano' => 1980], 'ano'],
            'desconhecida com decada' => [['tipo_data' => 'desconhecida', 'decada' => '1980'], 'decada'],

            // intervalos
            'dia acima do intervalo' => [['tipo_data' => 'data_exata', 'dia' => 32, 'mes' => 1, 'ano' => 1980], 'dia'],
            'dia abaixo do intervalo' => [['tipo_data' => 'data_exata', 'dia' => 0, 'mes' => 1, 'ano' => 1980], 'dia'],
            'mes acima do intervalo' => [['tipo_data' => 'data_exata', 'dia' => 1, 'mes' => 13, 'ano' => 1980], 'mes'],
            'mes abaixo do intervalo' => [['tipo_data' => 'data_exata', 'dia' => 1, 'mes' => 0, 'ano' => 1980], 'mes'],
            'ano abaixo do minimo' => [['tipo_data' => 'ano', 'ano' => 999], 'ano'],

            // datas impossíveis no calendário
            '31 de fevereiro' => [['tipo_data' => 'data_exata', 'dia' => 31, 'mes' => 2, 'ano' => 1980], 'dia'],
            '30 de fevereiro' => [['tipo_data' => 'data_exata', 'dia' => 30, 'mes' => 2, 'ano' => 1980], 'dia'],
            '29 de fevereiro fora de bissexto' => [
                ['tipo_data' => 'data_exata', 'dia' => 29, 'mes' => 2, 'ano' => 1981], 'dia',
            ],
            '31 de abril' => [['tipo_data' => 'data_exata', 'dia' => 31, 'mes' => 4, 'ano' => 1980], 'dia'],
        ];
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    #[DataProvider('combinacoesValidas')]
    public function test_aceita_combinacoes_validas(array $dados): void
    {
        $data = DataHistorica::deArray($dados);

        $this->assertSame([], $data->erros());
        $this->assertTrue($data->valida());
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    #[DataProvider('combinacoesInvalidas')]
    public function test_rejeita_combinacoes_invalidas(array $dados, string $campoComErro): void
    {
        $data = DataHistorica::deArray($dados);

        $this->assertFalse($data->valida());
        $this->assertArrayHasKey($campoComErro, $data->erros());
    }

    public function test_ano_futuro_e_rejeitado(): void
    {
        $data = DataHistorica::deArray(['tipo_data' => 'ano', 'ano' => (int) date('Y') + 1]);

        $this->assertArrayHasKey('ano', $data->erros());
    }

    public function test_persistencia_zera_campos_proibidos(): void
    {
        $data = DataHistorica::deArray(['tipo_data' => 'ano', 'dia' => 1, 'mes' => 2, 'ano' => 1980, 'decada' => '1980']);

        $this->assertSame([
            'tipo_data' => TipoData::Ano,
            'dia' => null,
            'mes' => null,
            'ano' => 1980,
            'decada' => null,
        ], $data->paraPersistencia());
    }

    public function test_formata_data_historica_para_exibicao(): void
    {
        $this->assertSame('15/03/1980', DataHistorica::deArray([
            'tipo_data' => 'data_exata',
            'dia' => 15,
            'mes' => 3,
            'ano' => 1980,
        ])->label());

        $this->assertSame('03/1980', DataHistorica::deArray([
            'tipo_data' => 'mes_ano',
            'mes' => 3,
            'ano' => 1980,
        ])->label());

        $this->assertSame('1980', DataHistorica::deArray([
            'tipo_data' => 'ano',
            'ano' => 1980,
        ])->label());

        $this->assertSame('Década de 1980', DataHistorica::deArray([
            'tipo_data' => 'decada',
            'decada' => '1980',
        ])->label());

        $this->assertSame('Data desconhecida', DataHistorica::deArray([
            'tipo_data' => 'desconhecida',
        ])->label());
    }

    public function test_campos_obrigatorios_e_proibidos_sao_complementares(): void
    {
        $esperado = TipoData::campos();
        sort($esperado);

        foreach (TipoData::cases() as $tipo) {
            $campos = array_merge($tipo->camposObrigatorios(), $tipo->camposProibidos());
            sort($campos);

            $this->assertSame($esperado, $campos, "tipo_data {$tipo->value}");
        }
    }
}
