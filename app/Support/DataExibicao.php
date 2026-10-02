<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Ponto único de conversão e formatação de instantes para exibição.
 *
 * O sistema grava e manipula tudo em UTC; esta classe traduz esses instantes
 * para o fuso de quem está vendo, sem nunca alterar o dado persistido.
 */
final class DataExibicao
{
    /** @var array<string, int>|null */
    private static ?array $identificadores = null;

    public function __construct(
        private readonly string $fuso,
    ) {
        if (! self::fusoValido($fuso)) {
            throw new InvalidArgumentException(
                "O fuso de exibição deve ser um identificador IANA válido, como America/Sao_Paulo. Recebido: {$fuso}.",
            );
        }
    }

    /**
     * Identificadores IANA acompanham mudanças de lei e horário de verão; um
     * offset fixo como -03:00 congela a regra no momento em que foi escrito.
     */
    public static function fusoValido(?string $fuso): bool
    {
        if ($fuso === null) {
            return false;
        }

        self::$identificadores ??= array_flip(DateTimeZone::listIdentifiers());

        return isset(self::$identificadores[$fuso]);
    }

    public function fuso(): string
    {
        return $this->fuso;
    }

    /**
     * Devolve a instância atual quando o fuso pedido não é utilizável, de modo
     * que um valor inválido apenas recaia no fallback em vez de quebrar a tela.
     */
    public function comFuso(?string $fuso): self
    {
        if (! self::fusoValido($fuso) || $fuso === $this->fuso) {
            return $this;
        }

        return new self($fuso);
    }

    public function converter(DateTimeInterface|string|null $valor): ?CarbonImmutable
    {
        $instante = $this->instante($valor);

        return $instante?->setTimezone($this->fuso);
    }

    public function formatar(DateTimeInterface|string|null $valor, ?string $formato = null): ?string
    {
        return $this->converter($valor)?->format(
            $formato ?? config('datas.formato_completo'),
        );
    }

    /**
     * Instante em UTC para o atributo `datetime`, que é o dado canônico lido
     * pelo JavaScript e por leitores de tela.
     */
    public function isoUtc(DateTimeInterface|string|null $valor): ?string
    {
        return $this->instante($valor)
            ?->setTimezone('UTC')
            ->format(DateTimeInterface::ATOM);
    }

    /**
     * Fronteiras do dia no fuso de exibição, devolvidas como instante em UTC.
     *
     * A conversão é parte do contrato: o binding de uma query formata o relógio
     * da instância e descarta o offset, de modo que entregar o horário local
     * compararia 23:59 de São Paulo com 23:59 de UTC.
     */
    public function inicioDoDia(string $data): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('Y-m-d', $data, $this->fuso)
            ->startOfDay()
            ->setTimezone('UTC');
    }

    public function fimDoDia(string $data): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('Y-m-d', $data, $this->fuso)
            ->endOfDay()
            ->setTimezone('UTC');
    }

    /**
     * Reconhece apenas strings que sejam, por inteiro, um ISO-8601 no formato
     * gravado pelo AuditSnapshot. Texto livre que mencione uma data no meio da
     * frase não é tocado.
     */
    public function tentarIso(string $valor): ?CarbonImmutable
    {
        $instante = DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $valor);
        $erros = DateTimeImmutable::getLastErrors();

        if ($instante === false || ($erros !== false && ($erros['warning_count'] ?? 0) + ($erros['error_count'] ?? 0) > 0)) {
            return null;
        }

        return CarbonImmutable::instance($instante);
    }

    private function instante(DateTimeInterface|string|null $valor): ?CarbonImmutable
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if ($valor instanceof DateTimeInterface) {
            return CarbonImmutable::instance($valor);
        }

        return $this->tentarIso($valor);
    }
}
