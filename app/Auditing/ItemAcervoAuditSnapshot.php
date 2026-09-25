<?php

namespace App\Auditing;

use App\Models\ItemAcervo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class ItemAcervoAuditSnapshot
{
    /** @var list<string> */
    public const FIELDS = [
        'tipo_item',
        'titulo',
        'legenda',
        'dia',
        'mes',
        'ano',
        'decada',
        'tipo_data',
        'local_atual',
        'local_epoca',
        'evento',
        'cedente',
        'estado_conservacao',
        'status',
        'visibilidade',
    ];

    /**
     * Relacionamentos auditados, com a coluna usada como rótulo histórico.
     *
     * @var array<string, string>
     */
    public const RELATIONSHIPS = [
        'categorias' => 'titulo',
        'assuntos' => 'titulo',
        'palavras_chave' => 'termo',
        'pessoas' => 'nome',
    ];

    /**
     * Captura campos e associações do item. Os vínculos guardam ID e rótulo
     * para que o histórico continue legível depois que a entidade relacionada
     * ou o próprio item deixarem de existir.
     */
    public static function capture(ItemAcervo $item): AuditSnapshot
    {
        $values = AuditSnapshot::fromModel($item, self::FIELDS)->values;
        $values['autor'] = self::autor($item);

        foreach (self::RELATIONSHIPS as $relationship => $labelColumn) {
            $values[$relationship] = self::related(
                self::relation($item, $relationship),
                $labelColumn,
            );
        }

        return AuditSnapshot::fromArray($values);
    }

    public static function captureDeletionState(ItemAcervo $item): AuditSnapshot
    {
        return AuditSnapshot::fromModel($item, ['deleted_at']);
    }

    /**
     * @return array{id: int, label: string}|null
     */
    private static function autor(ItemAcervo $item): ?array
    {
        $autor = $item->autor()->first(['id', 'nome']);

        return $autor === null ? null : self::reference($autor, 'nome');
    }

    /**
     * @return list<array{id: int, label: string}>
     */
    private static function related(BelongsToMany $relation, string $labelColumn): array
    {
        $table = $relation->getRelated()->getTable();

        return $relation
            ->orderBy("{$table}.id")
            ->get(["{$table}.id", "{$table}.{$labelColumn}"])
            ->map(fn (Model $model): array => self::reference($model, $labelColumn))
            ->values()
            ->all();
    }

    public static function relation(ItemAcervo $item, string $relationship): BelongsToMany
    {
        return match ($relationship) {
            'categorias' => $item->categorias(),
            'assuntos' => $item->assuntos(),
            'palavras_chave' => $item->palavrasChave(),
            'pessoas' => $item->pessoas(),
        };
    }

    /**
     * @return array{id: int, label: string}
     */
    private static function reference(Model $model, string $labelColumn): array
    {
        return [
            'id' => (int) $model->getKey(),
            'label' => (string) $model->getAttribute($labelColumn),
        ];
    }
}
