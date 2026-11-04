<?php

namespace Tests\Feature;

use App\Auditing\AuditContext;
use App\Auditing\AuditDiff;
use App\Auditing\AuditEventCollector;
use App\Auditing\AuditSnapshot;
use App\Auditing\AuditTransaction;
use App\Auditing\Enums\AuditAction;
use App\Auditing\Enums\AuditEntity;
use App\Auditing\Enums\AuditSource;
use App\Enums\Visibilidade;
use App\Models\AuditEvent;
use App\Models\Categoria;
use App\Models\ItemAcervo;
use App\Models\Pessoa;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use RuntimeException;
use Tests\TestCase;

class AuditServicePatternTest extends TestCase
{
    use RefreshDatabase;

    private function context(?int $actorUserId = null): AuditContext
    {
        return new AuditContext(
            actorUserId: $actorUserId,
            actorName: $actorUserId === null ? null : 'Responsável',
            actorRole: $actorUserId === null ? null : 'admin',
            source: AuditSource::Web,
            requestId: '30000000-0000-4000-8000-000000000003',
            correlationId: '40000000-0000-4000-8000-000000000004',
        );
    }

    public function test_snapshot_uses_an_explicit_field_list_and_normalizes_values(): void
    {
        $item = ItemAcervo::create([
            'tipo_item' => 'fotografia',
            'titulo' => 'Praça central',
            'visibilidade' => Visibilidade::Publico,
        ]);

        $snapshot = AuditSnapshot::fromModel($item, [
            'titulo',
            'visibilidade',
            'created_at',
        ]);

        $this->assertSame('Praça central', $snapshot->values['titulo']);
        $this->assertSame('publico', $snapshot->values['visibilidade']);
        $this->assertSame($item->created_at->format(DATE_ATOM), $snapshot->values['created_at']);
        $this->assertArrayNotHasKey('legenda', $snapshot->values);
    }

    public function test_diff_contains_only_fields_that_effectively_changed(): void
    {
        $diff = AuditDiff::between(
            AuditSnapshot::fromArray([
                'nome' => 'Maria',
                'email' => 'maria@example.test',
                'categoria_ids' => [1, 2],
            ]),
            AuditSnapshot::fromArray([
                'nome' => 'Maria Souza',
                'email' => 'maria@example.test',
                'categoria_ids' => [2, 3],
            ]),
        );

        $this->assertTrue($diff->hasChanges());
        $this->assertSame(['nome', 'categoria_ids'], $diff->changedFields);
        $this->assertSame([
            'nome' => 'Maria',
            'categoria_ids' => [1, 2],
        ], $diff->oldValues);
        $this->assertSame([
            'nome' => 'Maria Souza',
            'categoria_ids' => [2, 3],
        ], $diff->newValues);
    }

    public function test_successful_operation_commits_business_data_and_audit_together(): void
    {
        $actor = User::factory()->create();

        $categoria = app(AuditTransaction::class)->run(
            $this->context($actor->id),
            function (AuditEventCollector $audit): Categoria {
                $categoria = Categoria::create(['titulo' => 'Arquitetura']);

                $audit->capture(
                    action: AuditAction::Created,
                    subjectType: AuditEntity::Categoria,
                    subjectId: $categoria->id,
                    after: AuditSnapshot::fromModel($categoria, ['titulo', 'descricao']),
                    subjectLabel: $categoria->titulo,
                );

                return $categoria;
            },
        );

        $this->assertDatabaseHas('categorias', ['id' => $categoria->id]);
        $this->assertDatabaseCount('audit_events', 1);

        $event = AuditEvent::query()->firstOrFail();
        $this->assertSame(AuditAction::Created, $event->action);
        $this->assertNull($event->old_values);
        $this->assertSame([
            'titulo' => 'Arquitetura',
            'descricao' => null,
        ], $event->new_values);
        $this->assertSame(['titulo', 'descricao'], $event->metadata['changed_fields']);
    }

    public function test_operation_without_effective_changes_does_not_create_an_event(): void
    {
        $categoria = Categoria::create(['titulo' => 'Arquitetura']);

        app(AuditTransaction::class)->run(
            $this->context(),
            function (AuditEventCollector $audit) use ($categoria): void {
                $before = AuditSnapshot::fromModel($categoria, ['titulo', 'descricao']);
                $categoria->update(['titulo' => 'Arquitetura']);
                $after = AuditSnapshot::fromModel($categoria->refresh(), ['titulo', 'descricao']);

                $audit->capture(
                    action: AuditAction::Updated,
                    subjectType: AuditEntity::Categoria,
                    subjectId: $categoria->id,
                    before: $before,
                    after: $after,
                    subjectLabel: $categoria->titulo,
                );
            },
        );

        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_multiple_changes_to_the_same_entity_are_consolidated_into_one_event(): void
    {
        $pessoa = Pessoa::create([
            'nome' => 'Maria',
            'observacao' => 'Primeira observação',
        ]);

        app(AuditTransaction::class)->run(
            $this->context(),
            function (AuditEventCollector $audit) use ($pessoa): void {
                $beforeName = AuditSnapshot::fromModel($pessoa, ['nome']);
                $pessoa->update(['nome' => 'Maria Souza']);
                $afterName = AuditSnapshot::fromModel($pessoa->refresh(), ['nome']);

                $audit->capture(
                    AuditAction::Updated,
                    AuditEntity::Pessoa,
                    $pessoa->id,
                    $beforeName,
                    $afterName,
                    $pessoa->nome,
                );

                $beforeObservation = AuditSnapshot::fromModel($pessoa, ['observacao']);
                $pessoa->update(['observacao' => 'Observação final']);
                $afterObservation = AuditSnapshot::fromModel($pessoa->refresh(), ['observacao']);

                $audit->capture(
                    AuditAction::Updated,
                    AuditEntity::Pessoa,
                    $pessoa->id,
                    $beforeObservation,
                    $afterObservation,
                    $pessoa->nome,
                );
            },
        );

        $this->assertDatabaseCount('audit_events', 1);

        $event = AuditEvent::query()->firstOrFail();
        $this->assertSame([
            'nome' => 'Maria',
            'observacao' => 'Primeira observação',
        ], $event->old_values);
        $this->assertSame([
            'nome' => 'Maria Souza',
            'observacao' => 'Observação final',
        ], $event->new_values);
        $this->assertSame(['nome', 'observacao'], $event->metadata['changed_fields']);
    }

    public function test_change_reverted_inside_the_same_operation_does_not_create_an_event(): void
    {
        $categoria = Categoria::create(['titulo' => 'Original']);

        app(AuditTransaction::class)->run(
            $this->context(),
            function (AuditEventCollector $audit) use ($categoria): void {
                $before = AuditSnapshot::fromModel($categoria, ['titulo']);
                $categoria->update(['titulo' => 'Temporário']);
                $temporary = AuditSnapshot::fromModel($categoria->refresh(), ['titulo']);

                $audit->capture(
                    AuditAction::Updated,
                    AuditEntity::Categoria,
                    $categoria->id,
                    $before,
                    $temporary,
                );

                $beforeRevert = AuditSnapshot::fromModel($categoria, ['titulo']);
                $categoria->update(['titulo' => 'Original']);
                $afterRevert = AuditSnapshot::fromModel($categoria->refresh(), ['titulo']);

                $audit->capture(
                    AuditAction::Updated,
                    AuditEntity::Categoria,
                    $categoria->id,
                    $beforeRevert,
                    $afterRevert,
                );
            },
        );

        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_independent_entities_generate_correlated_events_in_the_same_transaction(): void
    {
        app(AuditTransaction::class)->run(
            $this->context(),
            function (AuditEventCollector $audit): void {
                foreach (['Arquitetura', 'Paisagem'] as $titulo) {
                    $categoria = Categoria::create(['titulo' => $titulo]);

                    $audit->capture(
                        AuditAction::Created,
                        AuditEntity::Categoria,
                        $categoria->id,
                        after: AuditSnapshot::fromModel($categoria, ['titulo']),
                        subjectLabel: $titulo,
                    );
                }
            },
        );

        $events = AuditEvent::query()->orderBy('id')->get();

        $this->assertCount(2, $events);
        $this->assertNotSame($events[0]->subject_id, $events[1]->subject_id);
        $this->assertSame($events[0]->request_id, $events[1]->request_id);
        $this->assertSame($events[0]->correlation_id, $events[1]->correlation_id);
    }

    public function test_deletion_uses_the_previous_snapshot_and_no_new_values(): void
    {
        $categoria = Categoria::create([
            'titulo' => 'Arquitetura',
            'descricao' => 'Patrimônio edificado',
        ]);

        app(AuditTransaction::class)->run(
            $this->context(),
            function (AuditEventCollector $audit) use ($categoria): void {
                $before = AuditSnapshot::fromModel($categoria, ['titulo', 'descricao']);
                $categoria->delete();

                $audit->capture(
                    action: AuditAction::Deleted,
                    subjectType: AuditEntity::Categoria,
                    subjectId: $categoria->id,
                    before: $before,
                    subjectLabel: $categoria->titulo,
                );
            },
        );

        $event = AuditEvent::query()->firstOrFail();
        $this->assertSame(AuditAction::Deleted, $event->action);
        $this->assertSame([
            'titulo' => 'Arquitetura',
            'descricao' => 'Patrimônio edificado',
        ], $event->old_values);
        $this->assertNull($event->new_values);
    }

    public function test_restoration_records_only_the_fields_changed_by_restoring(): void
    {
        $item = ItemAcervo::create([
            'tipo_item' => 'fotografia',
            'titulo' => 'Fotografia restaurável',
        ]);
        $item->delete();
        $item = ItemAcervo::onlyTrashed()->findOrFail($item->id);

        app(AuditTransaction::class)->run(
            $this->context(),
            function (AuditEventCollector $audit) use ($item): void {
                $before = AuditSnapshot::fromModel($item, ['deleted_at']);
                $item->restore();
                $after = AuditSnapshot::fromModel($item->refresh(), ['deleted_at']);

                $audit->capture(
                    AuditAction::Restored,
                    AuditEntity::ItemAcervo,
                    $item->id,
                    $before,
                    $after,
                    $item->titulo,
                );
            },
        );

        $event = AuditEvent::query()->firstOrFail();
        $this->assertSame(AuditAction::Restored, $event->action);
        $this->assertNotNull($event->old_values['deleted_at']);
        $this->assertSame(['deleted_at' => null], $event->new_values);
        $this->assertSame(['deleted_at'], $event->metadata['changed_fields']);
    }

    public function test_password_change_event_never_requires_or_stores_values(): void
    {
        $user = User::factory()->create();

        app(AuditTransaction::class)->run(
            $this->context($user->id),
            function (AuditEventCollector $audit) use ($user): void {
                $user->update(['password' => 'uma-nova-senha']);

                $audit->capture(
                    action: AuditAction::PasswordChanged,
                    subjectType: AuditEntity::User,
                    subjectId: $user->id,
                    subjectLabel: $user->nome,
                    metadata: ['method' => 'self_service'],
                );
            },
        );

        $event = AuditEvent::query()->firstOrFail();
        $this->assertSame(AuditAction::PasswordChanged, $event->action);
        $this->assertNull($event->old_values);
        $this->assertNull($event->new_values);
        $this->assertSame(['method' => 'self_service'], $event->metadata);
    }

    public function test_business_failure_rolls_back_data_and_does_not_write_pending_events(): void
    {
        try {
            app(AuditTransaction::class)->run(
                $this->context(),
                function (AuditEventCollector $audit): void {
                    $categoria = Categoria::create(['titulo' => 'Não persistir']);

                    $audit->capture(
                        AuditAction::Created,
                        AuditEntity::Categoria,
                        $categoria->id,
                        after: AuditSnapshot::fromModel($categoria, ['titulo']),
                    );

                    throw new RuntimeException('Falha na regra de negócio.');
                },
            );
        } catch (RuntimeException) {
            // A falha é intencional para validar o rollback.
        }

        $this->assertDatabaseMissing('categorias', ['titulo' => 'Não persistir']);
        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_audit_failure_rolls_back_the_business_operation(): void
    {
        $invalidContext = $this->context(999999);

        try {
            app(AuditTransaction::class)->run(
                $invalidContext,
                function (AuditEventCollector $audit): void {
                    $categoria = Categoria::create(['titulo' => 'Também não persistir']);

                    $audit->capture(
                        AuditAction::Created,
                        AuditEntity::Categoria,
                        $categoria->id,
                        after: AuditSnapshot::fromModel($categoria, ['titulo']),
                    );
                },
            );

            $this->fail('A chave estrangeira inválida deveria impedir a auditoria.');
        } catch (QueryException) {
            // O erro do AuditRecorder deve reverter também a alteração principal.
        }

        $this->assertDatabaseMissing('categorias', ['titulo' => 'Também não persistir']);
        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_different_actions_for_the_same_entity_are_rejected(): void
    {
        $categoria = Categoria::create(['titulo' => 'Arquitetura']);

        $this->expectException(LogicException::class);

        app(AuditTransaction::class)->run(
            $this->context(),
            function (AuditEventCollector $audit) use ($categoria): void {
                $snapshot = AuditSnapshot::fromModel($categoria, ['titulo']);

                $audit->capture(
                    AuditAction::Updated,
                    AuditEntity::Categoria,
                    $categoria->id,
                    AuditSnapshot::fromArray(['titulo' => 'Nome anterior']),
                    $snapshot,
                );

                $audit->capture(
                    AuditAction::Deleted,
                    AuditEntity::Categoria,
                    $categoria->id,
                    before: $snapshot,
                );
            },
        );
    }
}
