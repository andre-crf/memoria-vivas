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
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminAuditoriaConsultationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_only_administrators_can_access_audit_and_see_its_navigation(): void
    {
        $operator = $this->user('operador', 'Operador');
        $admin = $this->user('admin', 'Administrador');

        $this->get(route('admin.auditoria.index'))->assertRedirect(route('login'));

        $this->actingAs($operator)
            ->get(route('admin.auditoria.index'))
            ->assertForbidden();

        $this->actingAs($operator)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee(route('admin.auditoria.index'));

        $this->actingAs($admin)
            ->get(route('admin.auditoria.index'))
            ->assertOk()
            ->assertSee('Auditoria do sistema')
            ->assertSee(route('admin.auditoria.index'))
            ->assertSee('aria-current="page"', false);
    }

    public function test_listing_uses_historical_snapshots_and_orders_by_date_then_id(): void
    {
        $admin = $this->user('admin', 'Nome histórico');
        $older = $this->record(
            actor: $admin,
            occurredAt: '2026-09-20 10:00:00',
            subjectId: '10',
            subjectLabel: 'Registro antigo',
        );
        $sameTimeFirst = $this->record(
            actor: $admin,
            occurredAt: '2026-09-21 10:00:00',
            subjectId: '11',
            subjectLabel: 'Primeiro no mesmo horário',
        );
        $sameTimeLast = $this->record(
            actor: $admin,
            occurredAt: '2026-09-21 10:00:00',
            subjectId: '12',
            subjectLabel: 'Último no mesmo horário',
        );
        $admin->update(['nome' => 'Nome atual']);

        $response = $this->actingAs($admin)->get(route('admin.auditoria.index'));

        $response
            ->assertOk()
            ->assertSeeInOrder([
                "evento-auditoria-{$sameTimeLast->id}",
                "evento-auditoria-{$sameTimeFirst->id}",
                "evento-auditoria-{$older->id}",
            ])
            ->assertSee('Nome histórico')
            ->assertSee('Criação')
            ->assertSee('Categoria · #12')
            ->assertDontSee('old_values')
            ->assertDontSee('new_values')
            ->assertDontSee('metadata');
    }

    public function test_listing_distinguishes_empty_database_from_filters_without_results(): void
    {
        $admin = $this->user();

        $this->actingAs($admin)
            ->get(route('admin.auditoria.index'))
            ->assertOk()
            ->assertSee('Nenhum evento de auditoria registrado');

        $this->record(actor: $admin, subjectLabel: 'Evento existente');

        $this->actingAs($admin)
            ->get(route('admin.auditoria.index', ['acao' => AuditAction::Deleted->value]))
            ->assertOk()
            ->assertSee('Nenhum evento encontrado')
            ->assertDontSee('Nenhum evento de auditoria registrado');
    }

    public function test_date_period_filter_is_inclusive(): void
    {
        $admin = $this->user();
        $this->record($admin, '2026-09-19 23:59:59', subjectLabel: 'Antes');
        $this->record($admin, '2026-09-20 00:00:00', subjectLabel: 'No início');
        $this->record($admin, '2026-09-21 23:59:59', subjectLabel: 'No fim');
        $this->record($admin, '2026-09-22 00:00:00', subjectLabel: 'Depois');

        $this->actingAs($admin)
            ->get(route('admin.auditoria.index', [
                'data_inicio' => '2026-09-20',
                'data_fim' => '2026-09-21',
            ]))
            ->assertOk()
            ->assertSee('No início')
            ->assertSee('No fim')
            ->assertDontSee('Antes')
            ->assertDontSee('Depois');
    }

    public function test_responsible_filter_supports_active_inactive_and_system_events(): void
    {
        $admin = $this->user();
        $active = $this->user('operador', 'Ativo');
        $inactive = $this->user('operador', 'Inativo', 'inativo');
        $this->record($active, subjectLabel: 'Evento ativo');
        $this->record($inactive, subjectLabel: 'Evento inativo');
        $this->record(null, subjectLabel: 'Evento automático');

        $this->actingAs($admin)
            ->get(route('admin.auditoria.index', ['responsavel' => $inactive->id]))
            ->assertOk()
            ->assertSee('Evento inativo')
            ->assertDontSee('Evento ativo')
            ->assertDontSee('Evento automático')
            ->assertSee('Inativo (inativo)');

        $this->actingAs($admin)
            ->get(route('admin.auditoria.index', ['responsavel' => 'system']))
            ->assertOk()
            ->assertSee('Evento automático')
            ->assertSee('Sistema')
            ->assertDontSee('Evento ativo')
            ->assertDontSee('Evento inativo');
    }

    public function test_action_entity_and_entity_id_filters_can_be_combined(): void
    {
        $admin = $this->user();
        $this->record($admin, action: AuditAction::Updated, entity: AuditEntity::Categoria, subjectId: '15', subjectLabel: 'Resultado esperado');
        $this->record($admin, action: AuditAction::Created, entity: AuditEntity::Categoria, subjectId: '15', subjectLabel: 'Ação diferente');
        $this->record($admin, action: AuditAction::Updated, entity: AuditEntity::Assunto, subjectId: '15', subjectLabel: 'Entidade diferente');
        $this->record($admin, action: AuditAction::Updated, entity: AuditEntity::Categoria, subjectId: '16', subjectLabel: 'ID diferente');

        $this->actingAs($admin)
            ->get(route('admin.auditoria.index', [
                'acao' => AuditAction::Updated->value,
                'entidade' => AuditEntity::Categoria->value,
                'entidade_id' => '15',
            ]))
            ->assertOk()
            ->assertSee('Resultado esperado')
            ->assertDontSee('Ação diferente')
            ->assertDontSee('Entidade diferente')
            ->assertDontSee('ID diferente');
    }

    public function test_invalid_filters_are_rejected(): void
    {
        $admin = $this->user();
        $cases = [
            [['data_inicio' => '20/09/2026'], 'data_inicio'],
            [['data_inicio' => '2026-09-21', 'data_fim' => '2026-09-20'], 'data_fim'],
            [['responsavel' => '999999'], 'responsavel'],
            [['acao' => 'invalid'], 'acao'],
            [['entidade' => 'invalid'], 'entidade'],
            [['entidade_id' => '10'], 'entidade_id'],
        ];

        foreach ($cases as [$query, $field]) {
            $this->actingAs($admin)
                ->from(route('admin.auditoria.index'))
                ->get(route('admin.auditoria.index', $query))
                ->assertRedirect(route('admin.auditoria.index'))
                ->assertSessionHasErrors($field);
        }
    }

    public function test_pagination_has_twenty_five_items_and_preserves_filters(): void
    {
        $admin = $this->user();

        foreach (range(1, 26) as $id) {
            $this->record(
                actor: $admin,
                action: AuditAction::Created,
                subjectId: (string) $id,
                subjectLabel: "Evento {$id}",
            );
        }

        $response = $this->actingAs($admin)
            ->get(route('admin.auditoria.index', ['acao' => AuditAction::Created->value]));

        $response->assertOk();
        $this->assertSame(25, substr_count($response->getContent(), 'id="evento-auditoria-'));
        $response
            ->assertSee('acao=created', false)
            ->assertSee('page=2', false);
    }

    public function test_consultation_does_not_generate_or_modify_audit_events(): void
    {
        $admin = $this->user();
        $event = $this->record(actor: $admin, subjectLabel: 'Registro imutável');
        $before = $event->getAttributes();

        $this->actingAs($admin)
            ->get(route('admin.auditoria.index', ['acao' => AuditAction::Created->value]))
            ->assertOk();

        $this->assertDatabaseCount('audit_events', 1);
        $this->assertSame($before, $event->fresh()->getAttributes());
    }

    public function test_occurrence_index_migration_is_reversible(): void
    {
        $this->assertContains('audit_occurred_id_idx', $this->auditIndexNames());

        $migration = require database_path('migrations/2026_09_25_000001_add_occurrence_index_to_audit_events_table.php');
        $migration->down();

        $this->assertNotContains('audit_occurred_id_idx', $this->auditIndexNames());

        $migration->up();

        $this->assertContains('audit_occurred_id_idx', $this->auditIndexNames());
    }

    private function user(string $role = 'admin', string $name = 'Administrador', string $status = 'ativo'): User
    {
        return User::factory()->create([
            'nome' => $name,
            'role' => $role,
            'status' => $status,
        ]);
    }

    private function record(
        ?User $actor,
        string $occurredAt = '2026-09-25 12:00:00',
        AuditAction $action = AuditAction::Created,
        AuditEntity $entity = AuditEntity::Categoria,
        string $subjectId = '1',
        string $subjectLabel = 'Registro auditado',
    ): AuditEvent {
        CarbonImmutable::setTestNow(CarbonImmutable::parse($occurredAt, config('app.timezone')));
        $requestId = (string) Str::uuid();
        $context = $actor instanceof User
            ? AuditContext::forUser($actor, AuditSource::Web, $requestId)
            : AuditContext::forSystem(AuditSource::System, $requestId);

        return app(AuditRecorder::class)->record(
            $context,
            new AuditEventData(
                action: $action,
                subjectType: $entity,
                subjectId: $subjectId,
                subjectLabel: $subjectLabel,
                newValues: ['titulo' => $subjectLabel],
            ),
        );
    }

    /** @return list<string> */
    private function auditIndexNames(): array
    {
        return collect(Schema::getIndexes('audit_events'))
            ->pluck('name')
            ->filter()
            ->values()
            ->all();
    }
}
