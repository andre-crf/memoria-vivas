<?php

namespace Tests\Feature;

use App\Enums\TipoData;
use App\Models\ItemAcervo;
use App\Support\DataHistorica;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class DataHistoricaValidacaoTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $dados
     */
    private function validar(array $dados): \Illuminate\Validation\Validator
    {
        return Validator::make($dados, DataHistorica::regras());
    }

    public function test_regras_aceitam_cada_tipo_data_com_sua_combinacao(): void
    {
        $validos = [
            ['tipo_data' => 'data_exata', 'dia' => 15, 'mes' => 3, 'ano' => 1980],
            ['tipo_data' => 'mes_ano', 'mes' => 3, 'ano' => 1980],
            ['tipo_data' => 'ano', 'ano' => 1980],
            ['tipo_data' => 'decada', 'decada' => '1980'],
            ['tipo_data' => 'desconhecida'],
        ];

        foreach ($validos as $dados) {
            $this->assertTrue(
                $this->validar($dados)->passes(),
                'Deveria aceitar: '.json_encode($dados),
            );
        }
    }

    public function test_regras_rejeitam_campo_fora_do_tipo_data(): void
    {
        $validator = $this->validar(['tipo_data' => 'ano', 'ano' => 1980, 'dia' => 15]);

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString(
            'O campo dia deve ficar vazio',
            $validator->errors()->first('tipo_data'),
        );
    }

    public function test_regras_rejeitam_campo_obrigatorio_ausente(): void
    {
        $validator = $this->validar(['tipo_data' => 'data_exata', 'mes' => 3, 'ano' => 1980]);

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('obrigatório', $validator->errors()->first('tipo_data'));
    }

    public function test_regras_rejeitam_data_inexistente(): void
    {
        $validator = $this->validar(['tipo_data' => 'data_exata', 'dia' => 31, 'mes' => 2, 'ano' => 1980]);

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('não existe no calendário', $validator->errors()->first('tipo_data'));
    }

    public function test_regras_rejeitam_intervalos_invalidos(): void
    {
        $validator = $this->validar(['tipo_data' => 'data_exata', 'dia' => 40, 'mes' => 13, 'ano' => 1980]);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('dia'));
        $this->assertTrue($validator->errors()->has('mes'));
    }

    public function test_regras_rejeitam_tipo_data_desconhecido(): void
    {
        $validator = $this->validar(['tipo_data' => 'seculo']);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('tipo_data'));
    }

    public function test_item_acervo_expoe_a_data_historica_do_registro(): void
    {
        $item = new ItemAcervo([
            'titulo' => 'Foto sem dia',
            'tipo_item' => 'fotografia',
            'tipo_data' => 'mes_ano',
            'mes' => 3,
            'ano' => 1980,
        ]);

        $data = $item->dataHistorica();

        $this->assertSame(TipoData::MesAno, $data->tipoData);
        $this->assertTrue($data->valida());
    }
}
