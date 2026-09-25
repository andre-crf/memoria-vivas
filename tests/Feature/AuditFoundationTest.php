<?php

namespace Tests\Feature;

use App\Auditing\AuditContext;
use App\Auditing\AuditEventData;
use App\Auditing\AuditRecorder;
use App\Auditing\Enums\AuditAction;
use App\Auditing\Enums\AuditEntity;
use App\Auditing\Enums\AuditSource;
use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Tests\TestCase;

class AuditFoundationTest extends TestCase
{
    use RefreshDatabase;

    private function context(?User $user = null): AuditContext
    {
        if ($user === null) {
            return AuditContext::forSystem(
                source: AuditSource::System,
                requestId: '10000000-0000-4000-8000-000000000001',
                correlationId: '20000000-0000-4000-8000-000000000002',
            );
        }

        return AuditContext::forUser(
            user: $user,
            source: AuditSource::Web,
            requestId: '10000000-0000-4000-8000-000000000001',
            correlationId: '20000000-0000-4000-8000-000000000002',
        );
    }

    private function eventData(array $overrides = []): AuditEventData
    {
        return new AuditEventData(
            action: $overrides['action'] ?? AuditAction::Updated,
            subjectType: $overrides['subjectType'] ?? AuditEntity::ItemAcervo,
            subjectId: $overrides['subjectId'] ?? 42,
            subjectLabel: $overrides['subjectLabel'] ?? 'Praça central',
            oldValues: $overrides['oldValues'] ?? ['titulo' => 'Praça'],
            newValues: $overrides['newValues'] ?? ['titulo' => 'Praça central'],
            metadata: $overrides['metadata'] ?? ['changed_fields' => ['titulo']],
        );
    }

    public function test_audit_events_table_has_the_foundation_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('audit_events', [
            'id',
            'actor_user_id',
            'actor_name',
            'actor_role',
            'action',
            'subject_type',
            'subject_id',
            'subject_label',
            'old_values',
            'new_values',
            'metadata',
            'request_id',
            'correlation_id',
            'source',
            'occurred_at',
        ]));

        $this->assertFalse(Schema::hasColumn('audit_events', 'updated_at'));
    }

    public function test_recorder_persists_an_event_with_actor_snapshots_and_typed_values(): void
    {
        $actor = User::factory()->create([
            'nome' => 'Maria Auditora',
            'role' => 'admin',
        ]);

        $event = app(AuditRecorder::class)->record(
            $this->context($actor),
            $this->eventData(),
        );

        $this->assertSame($actor->id, $event->actor_user_id);
        $this->assertSame('Maria Auditora', $event->actor_name);
        $this->assertSame('admin', $event->actor_role);
        $this->assertSame(AuditAction::Updated, $event->action);
        $this->assertSame(AuditEntity::ItemAcervo, $event->subject_type);
        $this->assertSame('42', $event->subject_id);
        $this->assertSame('Praça central', $event->subject_label);
        $this->assertSame(['titulo' => 'Praça'], $event->old_values);
        $this->assertSame(['titulo' => 'Praça central'], $event->new_values);
        $this->assertSame(['changed_fields' => ['titulo']], $event->metadata);
        $this->assertSame(AuditSource::Web, $event->source);
        $this->assertSame('10000000-0000-4000-8000-000000000001', $event->request_id);
        $this->assertSame('20000000-0000-4000-8000-000000000002', $event->correlation_id);
        $this->assertNotNull($event->occurred_at);
        $this->assertTrue($event->actor->is($actor));
        $this->assertTrue($actor->eventosAuditoria()->firstOrFail()->is($event));
    }

    public function test_recorder_supports_system_events_without_an_actor(): void
    {
        $event = app(AuditRecorder::class)->record(
            $this->context(),
            $this->eventData([
                'action' => AuditAction::Created,
                'subjectType' => AuditEntity::Categoria,
                'subjectId' => 'catalog-import',
                'subjectLabel' => 'Importação de categorias',
            ]),
        );

        $this->assertNull($event->actor_user_id);
        $this->assertNull($event->actor_name);
        $this->assertNull($event->actor_role);
        $this->assertNull($event->actor);
        $this->assertSame(AuditSource::System, $event->source);
        $this->assertSame('catalog-import', $event->subject_id);
    }

    public function test_sensitive_values_are_removed_recursively_from_every_payload(): void
    {
        $event = app(AuditRecorder::class)->record(
            $this->context(),
            $this->eventData([
                'subjectType' => AuditEntity::User,
                'oldValues' => [
                    'email' => 'antigo@example.test',
                    'password' => 'hash-antigo',
                    'nested' => [
                        'remember_token' => 'segredo',
                        'nome' => 'Nome preservado',
                    ],
                ],
                'newValues' => [
                    'email' => 'novo@example.test',
                    'password_confirmation' => 'senha',
                    'password_hash' => 'hash-novo',
                ],
                'metadata' => [
                    'authorization' => 'Bearer segredo',
                    'request' => [
                        'api_token' => 'segredo',
                        'ip' => '127.0.0.1',
                    ],
                ],
            ]),
        );

        $this->assertSame([
            'email' => 'antigo@example.test',
            'nested' => ['nome' => 'Nome preservado'],
        ], $event->old_values);
        $this->assertSame(['email' => 'novo@example.test'], $event->new_values);
        $this->assertSame(['request' => ['ip' => '127.0.0.1']], $event->metadata);

        $stored = DB::table('audit_events')->where('id', $event->id)->first();
        $serialized = implode(' ', [
            (string) $stored->old_values,
            (string) $stored->new_values,
            (string) $stored->metadata,
        ]);

        $this->assertStringNotContainsString('segredo', $serialized);
        $this->assertStringNotContainsString('hash-antigo', $serialized);
        $this->assertStringNotContainsString('hash-novo', $serialized);
    }

    public function test_payload_that_only_contains_sensitive_values_is_stored_as_null(): void
    {
        $event = app(AuditRecorder::class)->record(
            $this->context(),
            $this->eventData([
                'oldValues' => ['password' => 'antiga'],
                'newValues' => ['password' => 'nova'],
                'metadata' => ['access_token' => 'segredo'],
            ]),
        );

        $this->assertNull($event->old_values);
        $this->assertNull($event->new_values);
        $this->assertNull($event->metadata);
    }

    public function test_event_insert_is_rolled_back_with_the_enclosing_transaction(): void
    {
        try {
            DB::transaction(function (): void {
                app(AuditRecorder::class)->record($this->context(), $this->eventData());

                throw new RuntimeException('Falha da operação principal.');
            });
        } catch (RuntimeException) {
            // A exceção apenas simula a falha da operação de aplicação.
        }

        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_audit_event_instances_cannot_be_saved_or_deleted(): void
    {
        $event = app(AuditRecorder::class)->record($this->context(), $this->eventData());

        try {
            $event->subject_label = 'Tentativa de alteração';
            $event->save();
            $this->fail('O evento deveria rejeitar alterações.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('AuditRecorder', $exception->getMessage());
        }

        try {
            $event->delete();
            $this->fail('O evento deveria rejeitar exclusões.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('imutáveis', $exception->getMessage());
        }

        $this->assertDatabaseHas('audit_events', [
            'id' => $event->id,
            'subject_label' => 'Praça central',
        ]);
    }

    public function test_audit_event_query_builder_rejects_direct_mutations(): void
    {
        $event = app(AuditRecorder::class)->record($this->context(), $this->eventData());

        try {
            AuditEvent::query()->whereKey($event->id)->update(['subject_label' => 'Alterado']);
            $this->fail('O builder deveria rejeitar alterações.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('AuditRecorder', $exception->getMessage());
        }

        try {
            AuditEvent::query()->whereKey($event->id)->delete();
            $this->fail('O builder deveria rejeitar exclusões.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('AuditRecorder', $exception->getMessage());
        }

        $this->assertDatabaseHas('audit_events', ['id' => $event->id]);
    }

    public function test_audit_event_cannot_be_created_directly_through_the_model(): void
    {
        try {
            AuditEvent::query()->insert([
                'action' => AuditAction::Created->value,
            ]);
            $this->fail('O builder deveria rejeitar inserções diretas.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('AuditRecorder', $exception->getMessage());
        }

        try {
            AuditEvent::create([]);
            $this->fail('O model deveria rejeitar inserções diretas.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('AuditRecorder', $exception->getMessage());
        }

        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_actor_with_audit_history_cannot_be_physically_deleted(): void
    {
        $actor = User::factory()->create();
        app(AuditRecorder::class)->record($this->context($actor), $this->eventData());

        $this->expectException(QueryException::class);

        $actor->delete();
    }

    public function test_action_and_entity_aliases_are_stable_strings(): void
    {
        $this->assertSame('force_deleted', AuditAction::ForceDeleted->value);
        $this->assertSame('password_changed', AuditAction::PasswordChanged->value);
        $this->assertSame('item_acervo', AuditEntity::ItemAcervo->value);
        $this->assertSame('palavra_chave', AuditEntity::PalavraChave->value);
        $this->assertSame('conjunto_contextual', AuditEntity::ConjuntoContextual->value);
    }

    public function test_audit_context_rejects_invalid_request_identifiers(): void
    {
        $this->expectException(InvalidArgumentException::class);

        AuditContext::forSystem(
            source: AuditSource::System,
            requestId: 'identificador-invalido',
        );
    }
}
