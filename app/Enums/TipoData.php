<?php

namespace App\Enums;

enum TipoData: string
{
    case DataExata = 'data_exata';
    case MesAno = 'mes_ano';
    case Ano = 'ano';
    case Decada = 'decada';
    case Desconhecida = 'desconhecida';

    /**
     * Campos de data que o item de acervo pode preencher.
     *
     * @return array<int, string>
     */
    public static function campos(): array
    {
        return ['dia', 'mes', 'ano', 'decada'];
    }

    /**
     * Campos que precisam estar preenchidos para este tipo de data.
     *
     * @return array<int, string>
     */
    public function camposObrigatorios(): array
    {
        return match ($this) {
            self::DataExata => ['dia', 'mes', 'ano'],
            self::MesAno => ['mes', 'ano'],
            self::Ano => ['ano'],
            self::Decada => ['decada'],
            self::Desconhecida => [],
        };
    }

    /**
     * Campos que precisam ficar vazios para este tipo de data.
     *
     * @return array<int, string>
     */
    public function camposProibidos(): array
    {
        return array_values(array_diff(self::campos(), $this->camposObrigatorios()));
    }

    public function label(): string
    {
        return match ($this) {
            self::DataExata => 'Data exata',
            self::MesAno => 'Mês e ano',
            self::Ano => 'Ano',
            self::Decada => 'Década',
            self::Desconhecida => 'Desconhecida',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
