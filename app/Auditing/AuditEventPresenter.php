<?php

namespace App\Auditing;

use App\Auditing\Enums\AuditEntity;
use App\Enums\TipoData;
use App\Enums\Visibilidade;
use App\Models\AuditEvent;
use App\Models\ItemAcervo;
use BackedEnum;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

final class AuditEventPresenter
{
    /** @var array<string, string> */
    private const FIELD_LABELS = [
        'action' => 'Ação',
        'added' => 'Adicionados',
        'applicable' => 'Aplicável',
        'arquivo_ids' => 'IDs dos arquivos',
        'assuntos' => 'Assuntos',
        'autor' => 'Autor',
        'categorias' => 'Categorias',
        'cedente' => 'Cedente',
        'change_groups' => 'Grupos alterados',
        'changed_fields' => 'Campos alterados',
        'colecao_capa_ids' => 'IDs das capas de coleção',
        'colecao_ids' => 'IDs das coleções',
        'conjunto_contextual_ids' => 'IDs dos conjuntos contextuais',
        'cascade' => 'Consequências da exclusão',
        'decada' => 'Década',
        'deleted_at' => 'Excluído em',
        'deletion_type' => 'Tipo de exclusão',
        'derivations' => 'Derivações',
        'derivations_removed' => 'Derivações removidas',
        'descricao' => 'Descrição',
        'dia' => 'Dia',
        'email' => 'E-mail',
        'estado_conservacao' => 'Estado de conservação',
        'evento' => 'Evento relacionado',
        'external_file_id' => 'ID externo do arquivo',
        'failed_versions' => 'Versões com falha',
        'file_size' => 'Tamanho do arquivo',
        'generated' => 'Derivações geradas',
        'height' => 'Altura',
        'id' => 'ID',
        'item_acervo' => 'Item do acervo',
        'item_acervo_id' => 'ID do item do acervo',
        'label' => 'Identificação',
        'legenda' => 'Legenda/descrição',
        'local_atual' => 'Local atual',
        'local_epoca' => 'Local na época',
        'mes' => 'Mês',
        'method' => 'Método',
        'mime_type' => 'Tipo MIME',
        'nome' => 'Nome',
        'nome_original' => 'Nome original',
        'observacao' => 'Observação',
        'operation' => 'Operação',
        'palavras_chave' => 'Palavras-chave',
        'pessoas' => 'Pessoas',
        'provider' => 'Provedor de armazenamento',
        'registro_downloads_count' => 'Registros de download removidos',
        'relationship_changes' => 'Alterações de relacionamentos',
        'removed' => 'Removidos',
        'role' => 'Perfil',
        'sha256' => 'SHA-256',
        'status' => 'Situação',
        'status_transition' => 'Transição de situação',
        'storage_path' => 'Caminho no armazenamento',
        'tipo_arquivo' => 'Tipo do arquivo',
        'tipo_data' => 'Precisão da data',
        'tipo_item' => 'Tipo do item',
        'titulo' => 'Título',
        'versao_arquivo' => 'Versão do arquivo',
        'visibilidade' => 'Visibilidade',
        'width' => 'Largura',
        'ano' => 'Ano',
    ];

    /** @var array<string, string> */
    private const VALUE_LABELS = [
        'admin' => 'Administrador',
        'operador' => 'Operador',
        'ativo' => 'Ativo',
        'inativo' => 'Inativo',
        'fotografia' => 'Fotografia',
        'imagem' => 'Imagem',
        'documento' => 'Documento',
        'audio' => 'Áudio',
        'video' => 'Vídeo',
        'outro' => 'Outro',
        'original' => 'Original',
        'thumbnail' => 'Miniatura',
        'medium' => 'Média',
        'large' => 'Grande',
        'local' => 'Armazenamento local',
        'identity' => 'Identidade',
        'authorization' => 'Autorização',
        'publication' => 'Publicação',
        'relationships' => 'Relacionamentos',
        'descriptive' => 'Dados descritivos',
        'permanent' => 'Definitiva',
        'deactivated' => 'Inativado',
        'activated' => 'Ativado',
        'current_password_confirmation' => 'Confirmação da senha atual',
    ];

    /** @var array<string, string> */
    private const OPERATION_LABELS = [
        'admin_user_create' => 'Cadastro administrativo de usuário',
        'admin_user_update' => 'Alteração administrativa de usuário',
        'self_profile_update' => 'Alteração do próprio perfil',
        'self_password_change' => 'Alteração da própria senha',
        'acervo_item_create' => 'Cadastro de item do acervo',
        'acervo_item_update' => 'Alteração de item do acervo',
        'acervo_item_soft_delete' => 'Exclusão de item do acervo',
        'acervo_item_restore' => 'Restauração de item do acervo',
        'acervo_item_force_delete' => 'Exclusão definitiva de item do acervo',
        'arquivo_original_upload' => 'Upload do arquivo original',
        'arquivo_original_replace' => 'Substituição do arquivo original',
        'arquivo_group_delete' => 'Remoção dos arquivos do item',
        'arquivo_orphan_delete' => 'Remoção de arquivo derivado sem original',
    ];

    /**
     * @return list<array{key: string, label: string, before: string, after: string}>
     */
    public function differences(AuditEvent $event): array
    {
        $before = $event->old_values ?? [];
        $after = $event->new_values ?? [];
        $keys = array_values(array_unique([...array_keys($before), ...array_keys($after)]));

        return array_map(fn (string $key): array => [
            'key' => $key,
            'label' => $this->fieldLabel($key),
            'before' => array_key_exists($key, $before)
                ? $this->value($before[$key], $key, $event->subject_type)
                : 'Não se aplica',
            'after' => array_key_exists($key, $after)
                ? $this->value($after[$key], $key, $event->subject_type)
                : 'Não se aplica',
        ], $keys);
    }

    /**
     * @return list<array{key: string, label: string, value: string}>
     */
    public function metadata(AuditEvent $event): array
    {
        return collect($event->metadata ?? [])
            ->map(fn (mixed $value, string $key): array => [
                'key' => $key,
                'label' => $this->fieldLabel($key),
                'value' => $this->value($value, $key, $event->subject_type),
            ])
            ->values()
            ->all();
    }

    public function fieldLabel(string $field): string
    {
        return self::FIELD_LABELS[$field]
            ?? Str::of($field)->replace('_', ' ')->lower()->ucfirst()->toString();
    }

    public function roleLabel(?string $role): string
    {
        if ($role === null || $role === '') {
            return 'Não registrado';
        }

        return self::VALUE_LABELS[$role] ?? Str::headline($role);
    }

    public function value(mixed $value, string $field, AuditEntity $entity, int $depth = 0): string
    {
        if ($value === null) {
            return 'Não informado';
        }

        if ($value instanceof BackedEnum) {
            $value = $value->value;
        }

        if ($value instanceof CarbonInterface) {
            return $value->format('d/m/Y H:i:s');
        }

        if (is_bool($value)) {
            return $value ? 'Sim' : 'Não';
        }

        if (is_array($value)) {
            return $this->arrayValue($value, $entity, $depth);
        }

        if ($field === 'file_size' && is_numeric($value)) {
            return $this->fileSize((int) $value);
        }

        if (in_array($field, ['width', 'height'], true) && is_numeric($value)) {
            return number_format((float) $value, 0, ',', '.').' px';
        }

        if ($field === 'operation' && is_string($value)) {
            return self::OPERATION_LABELS[$value] ?? Str::headline($value);
        }

        if ($field === 'tipo_data' && is_string($value)) {
            return TipoData::tryFrom($value)?->label() ?? Str::headline($value);
        }

        if ($field === 'visibilidade' && is_string($value)) {
            return Visibilidade::tryFrom($value)?->label() ?? Str::headline($value);
        }

        if ($field === 'estado_conservacao' && is_string($value)) {
            return ItemAcervo::ESTADOS_CONSERVACAO[$value] ?? Str::headline($value);
        }

        if ($field === 'status' && is_string($value) && $entity === AuditEntity::ItemAcervo) {
            return ItemAcervo::STATUS[$value] ?? Str::headline($value);
        }

        if (is_string($value)) {
            return self::VALUE_LABELS[$value] ?? $value;
        }

        return (string) $value;
    }

    /**
     * @param  array<array-key, mixed>  $values
     */
    private function arrayValue(array $values, AuditEntity $entity, int $depth): string
    {
        if ($values === []) {
            return 'Nenhum';
        }

        if ($this->isReference($values)) {
            return sprintf('%s (#%s)', $values['label'], $values['id']);
        }

        if (array_is_list($values)) {
            return collect($values)
                ->map(fn (mixed $value): string => $this->value($value, '', $entity, $depth + 1))
                ->implode($depth === 0 ? ', ' : '; ');
        }

        return collect($values)
            ->map(fn (mixed $value, string|int $key): string => sprintf(
                '%s: %s',
                is_string($key) ? $this->fieldLabel($key) : (string) $key,
                $this->value($value, (string) $key, $entity, $depth + 1),
            ))
            ->implode("\n");
    }

    /** @param array<array-key, mixed> $value */
    private function isReference(array $value): bool
    {
        return array_key_exists('id', $value)
            && array_key_exists('label', $value)
            && count(array_diff(array_keys($value), ['id', 'label'])) === 0;
    }

    private function fileSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return "{$bytes} bytes";
        }

        if ($bytes < 1024 * 1024) {
            return number_format($bytes / 1024, 1, ',', '.').' KB';
        }

        return number_format($bytes / (1024 * 1024), 1, ',', '.').' MB';
    }
}
