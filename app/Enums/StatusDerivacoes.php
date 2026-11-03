<?php

namespace App\Enums;

/**
 * Estado da geração das versões otimizadas, gravado no registro do original.
 *
 * Sem estado (null) significa que ele nunca foi registrado: originais anteriores ao
 * controle, até a próxima regeneração ou substituição.
 */
enum StatusDerivacoes: string
{
    case Pendente = 'pendente';
    case Processando = 'processando';
    case Concluido = 'concluido';
    case Falhou = 'falhou';
    // O original não é imagem (um PDF, por exemplo): não há versões a gerar.
    case NaoAplicavel = 'nao_aplicavel';

    public function label(): string
    {
        return match ($this) {
            self::Pendente => 'Pendente',
            self::Processando => 'Em processamento',
            self::Concluido => 'Concluído',
            self::Falhou => 'Falhou',
            self::NaoAplicavel => 'Não se aplica',
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
