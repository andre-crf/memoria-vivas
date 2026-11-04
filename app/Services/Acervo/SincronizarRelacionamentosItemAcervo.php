<?php

namespace App\Services\Acervo;

use App\Auditing\ItemAcervoAuditSnapshot;
use App\Models\ItemAcervo;

/**
 * Serviço auxiliar: sincroniza as associações N:N do item dentro da transação
 * aberta pelo serviço orquestrador. Não captura auditoria; o orquestrador
 * compara os snapshots completos do item antes e depois da operação.
 */
final class SincronizarRelacionamentosItemAcervo
{
    /**
     * Sincroniza somente os relacionamentos presentes em `$data`. Uma chave
     * ausente preserva os vínculos atuais; uma lista vazia remove todos.
     *
     * @param  array<string, mixed>  $data
     * @return bool Se algum vínculo foi adicionado ou removido.
     */
    public function execute(ItemAcervo $item, array $data): bool
    {
        $changed = false;

        foreach (array_keys(ItemAcervoAuditSnapshot::RELATIONSHIPS) as $relationship) {
            if (! array_key_exists($relationship, $data)) {
                continue;
            }

            $ids = array_values(array_unique(array_map('intval', $data[$relationship] ?? [])));
            $result = ItemAcervoAuditSnapshot::relation($item, $relationship)->sync($ids);

            $changed = $changed || $result['attached'] !== [] || $result['detached'] !== [];
        }

        return $changed;
    }
}
