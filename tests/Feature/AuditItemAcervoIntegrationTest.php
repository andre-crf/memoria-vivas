<?php

namespace Tests\Feature;

use App\Auditing\AuditContext;
use App\Auditing\Enums\AuditAction;
use App\Auditing\Enums\AuditEntity;
use App\Auditing\Enums\AuditSource;
use App\Enums\Visibilidade;
use App\Models\Arquivo;
use App\Models\Assunto;
use App\Models\AuditEvent;
use App\Models\Autor;
use App\Models\Categoria;
use App\Models\Colecao;
use App\Models\ItemAcervo;
use App\Models\PalavraChave;
use App\Models\Pessoa;
use App\Models\User;
use App\Services\Acervo\CriarItemAcervo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use JsonException;
use Tests\TestCase;

class AuditItemAcervoIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $role = 'admin', array $attributes = []): User
    {
        return User::factory()->create([
            'role' => $role,
            'status' => 'ativo',
            ...$attributes,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function fotografia(array $attributes = []): ItemAcervo
    {
        return ItemAcervo::create([
            'tipo_item' => 'fotografia',
            'titulo' => 'Praça central',
            'tipo_data' => 'ano',
            'ano' => 1980,
            'estado_conservacao' => 'bom',
            'status' => 'rascunho',
            'visibilidade' => Visibilidade::Privado,
            ...$attributes,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(ItemAcervo $fotografia, array $overrides = []): array
    {
        return [
            'titulo' => $fotografia->titulo,
            'legenda' => $fotografia->legenda,
            'tipo_data' => $fotografia->tipo_data->value,
            'ano' => $fotografia->ano,
            'local_atual' => $fotografia->local_atual,
            'local_epoca' => $fotografia->local_epoca,
            'evento' => $fotografia->evento,
            'cedente' => $fotografia->cedente,
            'estado_conservacao' => $fotografia->estado_conservacao,
            'status' => $fotografia->status,
            'visibilidade' => $fotografia->visibilidade->value,
            ...$overrides,
        ];
    }

    public function test_creation_records_fields_and_relationships_in_one_event(): void
    {
        $admin = $this->usuario('admin', ['nome' => 'Catalogadora']);
        $autor = Autor::create(['nome' => 'Foto Estrela', 'tipo' => 'instituicao']);
        $categoria = Categoria::create(['titulo' => 'Arquitetura']);
        $assunto = Assunto::create(['titulo' => 'Praças']);
        $palavra = PalavraChave::create(['termo' => 'coreto']);
        $pessoa = Pessoa::create(['nome' => 'João da Silva']);

        $this->actingAs($admin)
            ->post(route('admin.fotografias.store'), [
                'titulo' => 'Coreto da praça',
                'tipo_data' => 'ano',
                'ano' => 1975,
                'estado_conservacao' => 'bom',
                'status' => 'rascunho',
                'visibilidade' => Visibilidade::Privado->value,
                'autor_id' => $autor->id,
                'categorias' => [$categoria->id],
                'assuntos' => [$assunto->id],
                'palavras_chave' => [$palavra->id],
                'pessoas' => [$pessoa->id],
            ])
            ->assertRedirect(route('admin.fotografias.index'));

        $item = ItemAcervo::query()->where('titulo', 'Coreto da praça')->firstOrFail();
        $event = AuditEvent::query()->firstOrFail();

        $this->assertDatabaseCount('audit_events', 1);
        $this->assertSame(AuditAction::Created, $event->action);
        $this->assertSame(AuditEntity::ItemAcervo, $event->subject_type);
        $this->assertSame((string) $item->id, $event->subject_id);
        $this->assertSame('Coreto da praça', $event->subject_label);
        $this->assertSame($admin->id, $event->actor_user_id);
        $this->assertSame('Catalogadora', $event->actor_name);
        $this->assertNull($event->old_values);
        $this->assertSame('fotografia', $event->new_values['tipo_item']);
        $this->assertSame(1975, $event->new_values['ano']);
        $this->assertSame('ano', $event->new_values['tipo_data']);
        $this->assertSame('privado', $event->new_values['visibilidade']);
        $this->assertSame(['id' => $autor->id, 'label' => 'Foto Estrela'], $event->new_values['autor']);
        $this->assertSame([['id' => $categoria->id, 'label' => 'Arquitetura']], $event->new_values['categorias']);
        $this->assertSame([['id' => $assunto->id, 'label' => 'Praças']], $event->new_values['assuntos']);
        $this->assertSame([['id' => $palavra->id, 'label' => 'coreto']], $event->new_values['palavras_chave']);
        $this->assertSame([['id' => $pessoa->id, 'label' => 'João da Silva']], $event->new_values['pessoas']);
        $this->assertArrayNotHasKey('created_by_user_id', $event->new_values);
        $this->assertSame('acervo_item_create', $event->metadata['operation']);
        $this->assertSame('fotografia', $event->metadata['tipo_item']);
        $this->assertTrue(Str::isUuid($event->request_id));

        // O observer continua responsável pela autoria resumida.
        $this->assertSame($admin->id, $item->created_by_user_id);
        $this->assertSame($admin->id, $item->updated_by_user_id);
    }

    public function test_field_and_relationship_changes_are_consolidated_into_one_event(): void
    {
        $admin = $this->usuario();
        $mantida = Categoria::create(['titulo' => 'Arquitetura']);
        $removida = Categoria::create(['titulo' => 'Comércio']);
        $adicionada = Categoria::create(['titulo' => 'Lazer']);
        $pessoa = Pessoa::create(['nome' => 'Maria Souza']);
        $fotografia = $this->fotografia();
        $fotografia->categorias()->attach([$mantida->id, $removida->id]);

        $this->actingAs($admin)
            ->put(route('admin.fotografias.update', $fotografia), $this->payload($fotografia, [
                'titulo' => 'Praça central reformada',
                'status' => 'publicado',
                'categorias' => [$mantida->id, $adicionada->id],
                'pessoas' => [$pessoa->id],
            ]))
            ->assertRedirect(route('admin.fotografias.show', $fotografia));

        $event = AuditEvent::query()->firstOrFail();

        $this->assertDatabaseCount('audit_events', 1);
        $this->assertSame(AuditAction::Updated, $event->action);
        $this->assertSame('Praça central reformada', $event->subject_label);
        $this->assertSame([
            'titulo' => 'Praça central',
            'status' => 'rascunho',
            'categorias' => [
                ['id' => $mantida->id, 'label' => 'Arquitetura'],
                ['id' => $removida->id, 'label' => 'Comércio'],
            ],
            'pessoas' => [],
        ], $event->old_values);
        $this->assertSame([
            'titulo' => 'Praça central reformada',
            'status' => 'publicado',
            'categorias' => [
                ['id' => $mantida->id, 'label' => 'Arquitetura'],
                ['id' => $adicionada->id, 'label' => 'Lazer'],
            ],
            'pessoas' => [
                ['id' => $pessoa->id, 'label' => 'Maria Souza'],
            ],
        ], $event->new_values);
        $this->assertSame('acervo_item_update', $event->metadata['operation']);
        $this->assertSame(
            ['descriptive', 'publication', 'relationships'],
            $event->metadata['change_groups'],
        );
        $this->assertSame(['titulo', 'status', 'categorias', 'pessoas'], $event->metadata['changed_fields']);
        $this->assertSame([
            'categorias' => ['added' => [$adicionada->id], 'removed' => [$removida->id]],
            'pessoas' => ['added' => [$pessoa->id], 'removed' => []],
        ], $event->metadata['relationship_changes']);
    }

    public function test_relationship_only_change_records_event_and_updates_summary_authorship(): void
    {
        $criador = $this->usuario();
        $editor = $this->usuario('operador');
        $this->actingAs($criador);
        $fotografia = $this->fotografia();
        $autor = Autor::create(['nome' => 'Estúdio Central']);
        $assunto = Assunto::create(['titulo' => 'Festas']);

        $this->actingAs($editor)
            ->put(route('admin.fotografias.update', $fotografia), $this->payload($fotografia, [
                'autor_id' => $autor->id,
                'assuntos' => [$assunto->id],
            ]))
            ->assertRedirect(route('admin.fotografias.show', $fotografia));

        $event = AuditEvent::query()->firstOrFail();

        $this->assertSame(AuditAction::Updated, $event->action);
        $this->assertSame($editor->id, $event->actor_user_id);
        $this->assertSame(['autor' => null, 'assuntos' => []], $event->old_values);
        $this->assertSame([
            'autor' => ['id' => $autor->id, 'label' => 'Estúdio Central'],
            'assuntos' => [['id' => $assunto->id, 'label' => 'Festas']],
        ], $event->new_values);
        $this->assertSame(['relationships'], $event->metadata['change_groups']);
        $this->assertSame($editor->id, $fotografia->fresh()->updated_by_user_id);
        $this->assertSame($criador->id, $fotografia->fresh()->created_by_user_id);
    }

    public function test_unchanged_submission_and_omitted_relationships_do_not_generate_events(): void
    {
        $admin = $this->usuario();
        $categoria = Categoria::create(['titulo' => 'Arquitetura']);
        $fotografia = $this->fotografia();
        $fotografia->categorias()->attach($categoria);

        $this->actingAs($admin)
            ->put(route('admin.fotografias.update', $fotografia), $this->payload($fotografia))
            ->assertRedirect(route('admin.fotografias.show', $fotografia));

        $this->actingAs($admin)
            ->put(route('admin.fotografias.update', $fotografia), $this->payload($fotografia, [
                'categorias' => [$categoria->id],
            ]))
            ->assertRedirect(route('admin.fotografias.show', $fotografia));

        $this->assertDatabaseCount('audit_events', 0);
        $this->assertDatabaseHas('categoria_item_acervo', [
            'categoria_id' => $categoria->id,
            'item_acervo_id' => $fotografia->id,
        ]);
    }

    public function test_empty_relationship_list_removes_links_and_records_the_difference(): void
    {
        $admin = $this->usuario();
        $palavra = PalavraChave::create(['termo' => 'bonde']);
        $fotografia = $this->fotografia();
        $fotografia->palavrasChave()->attach($palavra);

        $this->actingAs($admin)
            ->put(route('admin.fotografias.update', $fotografia), $this->payload($fotografia, [
                'palavras_chave' => [],
            ]))
            ->assertRedirect(route('admin.fotografias.show', $fotografia));

        $event = AuditEvent::query()->firstOrFail();

        $this->assertSame(['palavras_chave' => [['id' => $palavra->id, 'label' => 'bonde']]], $event->old_values);
        $this->assertSame(['palavras_chave' => []], $event->new_values);
        $this->assertSame(
            ['palavras_chave' => ['added' => [], 'removed' => [$palavra->id]]],
            $event->metadata['relationship_changes'],
        );
        $this->assertDatabaseCount('item_acervo_palavra_chave', 0);
    }

    public function test_invalid_relationship_is_rejected_without_changes_or_events(): void
    {
        $admin = $this->usuario();
        $fotografia = $this->fotografia();

        $this->actingAs($admin)
            ->from(route('admin.fotografias.edit', $fotografia))
            ->put(route('admin.fotografias.update', $fotografia), $this->payload($fotografia, [
                'titulo' => 'Título que não deve persistir',
                'categorias' => [999],
            ]))
            ->assertRedirect(route('admin.fotografias.edit', $fotografia))
            ->assertSessionHasErrors('categorias.0');

        $this->assertDatabaseCount('audit_events', 0);
        $this->assertSame('Praça central', $fotografia->fresh()->titulo);
    }

    public function test_soft_delete_records_the_complete_previous_snapshot(): void
    {
        $operador = $this->usuario('operador');
        $categoria = Categoria::create(['titulo' => 'Arquitetura']);
        $fotografia = $this->fotografia();
        $fotografia->categorias()->attach($categoria);

        $this->actingAs($operador)
            ->delete(route('admin.fotografias.destroy', $fotografia))
            ->assertRedirect(route('admin.fotografias.index'));

        $event = AuditEvent::query()->firstOrFail();

        $this->assertSoftDeleted('item_acervos', ['id' => $fotografia->id]);
        $this->assertSame($operador->id, ItemAcervo::withTrashed()->findOrFail($fotografia->id)->deleted_by_user_id);
        $this->assertDatabaseCount('audit_events', 1);
        $this->assertSame(AuditAction::Deleted, $event->action);
        $this->assertNull($event->new_values);
        $this->assertSame('Praça central', $event->old_values['titulo']);
        $this->assertSame('rascunho', $event->old_values['status']);
        $this->assertSame([['id' => $categoria->id, 'label' => 'Arquitetura']], $event->old_values['categorias']);
        $this->assertSame('acervo_item_soft_delete', $event->metadata['operation']);
        $this->assertSame('soft', $event->metadata['deletion_type']);
    }

    public function test_restoration_records_only_the_deletion_state_difference(): void
    {
        $admin = $this->usuario();
        $fotografia = $this->fotografia();
        $fotografia->delete();
        $deletedAt = ItemAcervo::withTrashed()->findOrFail($fotografia->id)->deleted_at;

        $this->actingAs($admin)
            ->patch(route('admin.fotografias.restore', $fotografia->id))
            ->assertRedirect(route('admin.fotografias.trashed'));

        $event = AuditEvent::query()->firstOrFail();

        $this->assertNotSoftDeleted('item_acervos', ['id' => $fotografia->id]);
        $this->assertSame(AuditAction::Restored, $event->action);
        $this->assertSame(['deleted_at' => $deletedAt->format(DATE_ATOM)], $event->old_values);
        $this->assertSame(['deleted_at' => null], $event->new_values);
        $this->assertSame(['deleted_at'], $event->metadata['changed_fields']);
        $this->assertSame('acervo_item_restore', $event->metadata['operation']);
        $this->assertSame($admin->id, $fotografia->fresh()->updated_by_user_id);
    }

    public function test_force_delete_preserves_snapshot_and_reports_cascade_consequences(): void
    {
        $admin = $this->usuario();
        $autor = Autor::create(['nome' => 'Foto Estrela']);
        $categoria = Categoria::create(['titulo' => 'Arquitetura']);
        $pessoa = Pessoa::create(['nome' => 'Maria Souza']);
        $fotografia = $this->fotografia(['autor_id' => $autor->id]);
        $fotografia->categorias()->attach($categoria);
        $fotografia->pessoas()->attach($pessoa);
        $colecao = Colecao::create(['titulo' => 'Centro histórico', 'item_capa_id' => $fotografia->id]);
        $colecao->itensAcervo()->attach($fotografia);
        $arquivo = Arquivo::create([
            'item_acervo_id' => $fotografia->id,
            'nome_original' => 'praca.jpg',
            'provider' => 'local',
            'storage_path' => 'acervo/praca.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 2048,
            'tipo_arquivo' => 'imagem',
            'versao_arquivo' => 'original',
        ]);
        $fotografia->delete();

        $this->actingAs($admin)
            ->delete(route('admin.fotografias.force-destroy', $fotografia->id))
            ->assertRedirect(route('admin.fotografias.trashed'));

        $this->assertDatabaseMissing('item_acervos', ['id' => $fotografia->id]);
        $this->assertDatabaseCount('categoria_item_acervo', 0);

        // Mesmo após remover as entidades relacionadas, o histórico continua legível.
        $categoria->delete();
        $pessoa->delete();
        $autor->delete();

        $event = AuditEvent::query()
            ->where('subject_type', AuditEntity::ItemAcervo)
            ->firstOrFail();

        $this->assertDatabaseCount('audit_events', 2);
        $this->assertSame(AuditAction::ForceDeleted, $event->action);
        $this->assertSame((string) $fotografia->id, $event->subject_id);
        $this->assertSame('Praça central', $event->subject_label);
        $this->assertNull($event->new_values);
        $this->assertSame('Praça central', $event->old_values['titulo']);
        $this->assertNotNull($event->old_values['deleted_at']);
        $this->assertSame(['id' => $autor->id, 'label' => 'Foto Estrela'], $event->old_values['autor']);
        $this->assertSame([['id' => $categoria->id, 'label' => 'Arquitetura']], $event->old_values['categorias']);
        $this->assertSame([['id' => $pessoa->id, 'label' => 'Maria Souza']], $event->old_values['pessoas']);
        $this->assertSame('acervo_item_force_delete', $event->metadata['operation']);
        $this->assertSame('permanent', $event->metadata['deletion_type']);
        $this->assertSame([
            'arquivo_ids' => [$arquivo->id],
            'colecao_ids' => [$colecao->id],
            'conjunto_contextual_ids' => [],
            'colecao_capa_ids' => [$colecao->id],
            'registro_downloads_count' => 0,
        ], $event->metadata['cascade']);

        $arquivoEvent = AuditEvent::query()
            ->where('subject_type', AuditEntity::Arquivo)
            ->firstOrFail();

        $this->assertSame(AuditAction::Deleted, $arquivoEvent->action);
        $this->assertSame((string) $arquivo->id, $arquivoEvent->subject_id);
        $this->assertSame('arquivo_group_delete', $arquivoEvent->metadata['operation']);
        $this->assertSame($event->request_id, $arquivoEvent->request_id);
        $this->assertSame($event->correlation_id, $arquivoEvent->correlation_id);
    }

    public function test_unauthorized_restore_and_force_delete_do_not_generate_events(): void
    {
        $operador = $this->usuario('operador');
        $fotografia = $this->fotografia();
        $fotografia->delete();

        $this->actingAs($operador)
            ->patch(route('admin.fotografias.restore', $fotografia->id))
            ->assertForbidden();

        $this->actingAs($operador)
            ->delete(route('admin.fotografias.force-destroy', $fotografia->id))
            ->assertForbidden();

        $this->assertSoftDeleted('item_acervos', ['id' => $fotografia->id]);
        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_observer_only_maintains_summary_authorship(): void
    {
        $usuario = $this->usuario();
        $this->actingAs($usuario);

        $fotografia = $this->fotografia();
        $fotografia->update(['titulo' => 'Alteração fora de serviço']);
        $fotografia->delete();
        $fotografia->restore();

        $this->assertDatabaseCount('audit_events', 0);
        $this->assertSame($usuario->id, $fotografia->fresh()->updated_by_user_id);
    }

    public function test_audit_persistence_failure_rolls_back_item_and_relationships(): void
    {
        $admin = $this->usuario();
        $categoria = Categoria::create(['titulo' => 'Arquitetura']);
        $context = new AuditContext(
            actorUserId: $admin->id,
            actorName: $admin->nome,
            actorRole: $admin->role,
            source: AuditSource::Web,
            requestId: (string) Str::uuid(),
            correlationId: (string) Str::uuid(),
        );

        try {
            app(CriarItemAcervo::class)->execute($admin, [
                'tipo_item' => 'fotografia',
                'titulo' => "Fotografia \xB1 inválida",
                'status' => 'rascunho',
                'categorias' => [$categoria->id],
            ], $context);

            $this->fail('A persistência da auditoria deveria falhar com JSON inválido.');
        } catch (JsonException) {
            // A falha na auditoria deve reverter também o item e seus vínculos.
        }

        $this->assertDatabaseCount('item_acervos', 0);
        $this->assertDatabaseCount('categoria_item_acervo', 0);
        $this->assertDatabaseCount('audit_events', 0);
    }
}
