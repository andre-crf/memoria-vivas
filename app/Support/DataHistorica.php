<?php

namespace App\Support;

use App\Enums\TipoData;
use App\Rules\DataHistoricaValida;
use Illuminate\Validation\Rule;

/**
 * Valida a combinação de campos de data histórica de um item de acervo.
 *
 * Regra única e reutilizável: o Model, a Rule de validação e os formulários
 * Livewire delegam para esta classe em vez de repetir as comparações.
 */
final class DataHistorica
{
    public const ANO_MINIMO = 1000;

    /**
     * Década é gravada como o ano inicial: "1980", "1990".
     */
    public const REGEX_DECADA = '/^\d{3}0$/';

    private const LABELS = [
        'dia' => 'dia',
        'mes' => 'mês',
        'ano' => 'ano',
        'decada' => 'década',
    ];

    public function __construct(
        public readonly TipoData $tipoData,
        public readonly ?int $dia = null,
        public readonly ?int $mes = null,
        public readonly ?int $ano = null,
        public readonly ?string $decada = null,
    ) {}

    /**
     * Monta a partir de dados crus (formulário, request, atributos do Model).
     *
     * Strings vazias contam como ausência de valor.
     *
     * @param  array<string, mixed>  $dados
     */
    public static function deArray(array $dados): self
    {
        $tipoData = $dados['tipo_data'] ?? null;

        return new self(
            $tipoData instanceof TipoData ? $tipoData : TipoData::from((string) $tipoData),
            self::inteiroOuNulo($dados['dia'] ?? null),
            self::inteiroOuNulo($dados['mes'] ?? null),
            self::inteiroOuNulo($dados['ano'] ?? null),
            self::textoOuNulo($dados['decada'] ?? null),
        );
    }

    /**
     * Regras de validação prontas para FormRequest / Livewire.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function regras(): array
    {
        return [
            'tipo_data' => ['required', Rule::enum(TipoData::class), new DataHistoricaValida],
            'dia' => ['nullable', 'integer', 'between:1,31'],
            'mes' => ['nullable', 'integer', 'between:1,12'],
            'ano' => ['nullable', 'integer', 'between:'.self::ANO_MINIMO.','.self::anoMaximo()],
            'decada' => ['nullable', 'string', 'regex:'.self::REGEX_DECADA],
        ];
    }

    public static function anoMaximo(): int
    {
        return (int) date('Y');
    }

    public function valida(): bool
    {
        return $this->erros() === [];
    }

    /**
     * Erros encontrados, no máximo um por campo.
     *
     * @return array<string, string>
     */
    public function erros(): array
    {
        $erros = $this->errosDePreenchimento();
        $erros += $this->errosDeIntervalo();

        if ($erros === [] && $this->tipoData === TipoData::DataExata && ! checkdate($this->mes, $this->dia, $this->ano)) {
            $erros['dia'] = sprintf('A data %02d/%02d/%d não existe no calendário.', $this->dia, $this->mes, $this->ano);
        }

        return $erros;
    }

    /**
     * Valores normalizados para persistência: os campos proibidos viram null.
     *
     * @return array<string, mixed>
     */
    public function paraPersistencia(): array
    {
        $valores = [
            'tipo_data' => $this->tipoData,
            'dia' => $this->dia,
            'mes' => $this->mes,
            'ano' => $this->ano,
            'decada' => $this->decada,
        ];

        foreach ($this->tipoData->camposProibidos() as $campo) {
            $valores[$campo] = null;
        }

        return $valores;
    }

    public function label(): string
    {
        return match ($this->tipoData) {
            TipoData::DataExata => sprintf('%02d/%02d/%d', $this->dia, $this->mes, $this->ano),
            TipoData::MesAno => sprintf('%02d/%d', $this->mes, $this->ano),
            TipoData::Ano => (string) $this->ano,
            TipoData::Decada => "Década de {$this->decada}",
            TipoData::Desconhecida => 'Data desconhecida',
        };
    }

    /**
     * @return array<string, string>
     */
    private function errosDePreenchimento(): array
    {
        $erros = [];

        foreach ($this->tipoData->camposObrigatorios() as $campo) {
            if ($this->valor($campo) === null) {
                $erros[$campo] = sprintf(
                    'O campo %s é obrigatório quando o tipo de data é "%s".',
                    self::LABELS[$campo],
                    $this->tipoData->label(),
                );
            }
        }

        foreach ($this->tipoData->camposProibidos() as $campo) {
            if ($this->valor($campo) !== null) {
                $erros[$campo] = sprintf(
                    'O campo %s deve ficar vazio quando o tipo de data é "%s".',
                    self::LABELS[$campo],
                    $this->tipoData->label(),
                );
            }
        }

        return $erros;
    }

    /**
     * @return array<string, string>
     */
    private function errosDeIntervalo(): array
    {
        $erros = [];

        if ($this->dia !== null && ($this->dia < 1 || $this->dia > 31)) {
            $erros['dia'] = 'O dia deve estar entre 1 e 31.';
        }

        if ($this->mes !== null && ($this->mes < 1 || $this->mes > 12)) {
            $erros['mes'] = 'O mês deve estar entre 1 e 12.';
        }

        if ($this->ano !== null && ($this->ano < self::ANO_MINIMO || $this->ano > self::anoMaximo())) {
            $erros['ano'] = sprintf('O ano deve estar entre %d e %d.', self::ANO_MINIMO, self::anoMaximo());
        }

        if ($this->decada !== null && preg_match(self::REGEX_DECADA, $this->decada) !== 1) {
            $erros['decada'] = 'A década deve ser o ano inicial com quatro dígitos terminados em zero, como "1980".';
        }

        return $erros;
    }

    private function valor(string $campo): int|string|null
    {
        return match ($campo) {
            'dia' => $this->dia,
            'mes' => $this->mes,
            'ano' => $this->ano,
            'decada' => $this->decada,
        };
    }

    private static function inteiroOuNulo(mixed $valor): ?int
    {
        return $valor === null || $valor === '' ? null : (int) $valor;
    }

    private static function textoOuNulo(mixed $valor): ?string
    {
        return $valor === null || $valor === '' ? null : (string) $valor;
    }
}
