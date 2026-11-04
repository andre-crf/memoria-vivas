<?php

namespace App\Auditing;

use App\Models\Arquivo;
use Illuminate\Support\Enumerable;

final class ArquivoAuditSnapshot
{
    /** @var list<string> */
    public const FIELDS = [
        'item_acervo_id',
        'nome_original',
        'provider',
        'external_file_id',
        'storage_path',
        'mime_type',
        'file_size',
        'tipo_arquivo',
        'sha256',
        'versao_arquivo',
        'width',
        'height',
    ];

    public static function capture(Arquivo $arquivo): AuditSnapshot
    {
        return AuditSnapshot::fromModel($arquivo, self::FIELDS);
    }

    /**
     * @param  iterable<int, Arquivo>  $arquivos
     * @return list<array<string, mixed>>
     */
    public static function summaries(iterable $arquivos): array
    {
        $items = $arquivos instanceof Enumerable ? $arquivos : collect($arquivos);

        return $items
            ->map(fn (Arquivo $arquivo): array => [
                'id' => (int) $arquivo->getKey(),
                ...self::capture($arquivo)->values,
            ])
            ->values()
            ->all();
    }
}
