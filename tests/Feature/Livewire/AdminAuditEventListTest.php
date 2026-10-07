<?php

namespace Tests\Feature\Livewire;

use App\Auditing\AuditContext;
use App\Auditing\AuditEventData;
use App\Auditing\AuditRecorder;
use App\Auditing\Enums\AuditAction;
use App\Auditing\Enums\AuditEntity;
use App\Auditing\Enums\AuditSource;
use App\Livewire\Admin\AuditEventList;
use App\Models\AuditEvent;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class AdminAuditEventListTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        Paginator::useTailwind();

        parent::tearDown();
    }

    public function test_component_renders_for_an_administrator(): void
    {
        $admin = $this->user();
        $this->record($admin, subjectLabel: 'Evento renderizado');

        Livewire::actingAs($admin);

        Livewire::test(AuditEventList::class)
            ->assertSee('Filtros')
            ->assertSee('Evento renderizado')
            ->assertSee('wire:model.live="acao"', false);
    }

    public function test_component_authorizes_every_request(): void
    {
        Livewire::actingAs($this->user('operador'));

        Livewire::test(AuditEventList::class)
            ->assertForbidden();
    }

    public function test_individual_filters_update_the_results(): void
    {
        $admin = $this->user();
        $other = $this->user('operador');
        $expected = $this->record(
            $other,
            occurredAt: '2026-09-21 12:00:00',
            action: AuditAction::Updated,
            entity: AuditEntity::Assunto,
            subjectId: '42',
            subjectLabel: 'Resultado esperado',
        );
        $this->record(
            $admin,
            occurredAt: '2026-09-19 12:00:00',
            action: AuditAction::Created,
            entity: AuditEntity::Categoria,
            subjectId: '99',
            subjectLabel: 'Resultado diferente',
        );

        Livewire::actingAs($admin);

        Livewire::test(AuditEventList::class)
            ->set('data_inicio', '2026-09-20')
            ->assertSee('Resultado esperado')
            ->assertDontSee('Resultado diferente');

        Livewire::test(AuditEventList::class)
            ->set('data_fim', '2026-09-20')
            ->assertSee('Resultado diferente')
            ->assertDontSee('Resultado esperado');

        Livewire::test(AuditEventList::class)
            ->set('responsavel', (string) $other->id)
            ->assertSee('Resultado esperado')
            ->assertDontSee('Resultado diferente');

        Livewire::test(AuditEventList::class)
            ->set('acao', AuditAction::Updated->value)
            ->assertSee('Resultado esperado')
            ->assertDontSee('Resultado diferente');

        Livewire::test(AuditEventList::class)
            ->set('entidade', AuditEntity::Assunto->value)
            ->assertSee('Resultado esperado')
            ->assertDontSee('Resultado diferente');

        Livewire::test(AuditEventList::class)
            ->set('entidade', AuditEntity::Assunto->value)
            ->set('entidade_id', $expected->subject_id)
            ->assertSee('Resultado esperado')
            ->assertDontSee('Resultado diferente');
    }

    public function test_filters_can_be_combined(): void
    {
        $admin = $this->user();
        $expected = $this->record($admin, action: AuditAction::Updated, subjectId: '15', subjectLabel: 'Combinado');
        $this->record($admin, action: AuditAction::Created, subjectId: '15', subjectLabel: 'Ação diferente');
        $this->record($admin, action: AuditAction::Updated, entity: AuditEntity::Assunto, subjectId: '15', subjectLabel: 'Entidade diferente');

        Livewire::actingAs($admin);

        Livewire::test(AuditEventList::class)
            ->set('acao', AuditAction::Updated->value)
            ->set('entidade', AuditEntity::Categoria->value)
            ->set('entidade_id', $expected->subject_id)
            ->assertSee('Combinado')
            ->assertDontSee('Ação diferente')
            ->assertDontSee('Entidade diferente');
    }

    public function test_changing_a_filter_resets_pagination(): void
    {
        $admin = $this->user();

        foreach (range(1, 30) as $id) {
            $this->record($admin, subjectId: (string) $id, subjectLabel: "Evento {$id}");
        }

        Livewire::actingAs($admin);

        Livewire::test(AuditEventList::class)
            ->call('setPage', 2)
            ->assertSet('paginators.page', 2)
            ->set('acao', AuditAction::Created->value)
            ->assertSet('paginators.page', 1);
    }

    public function test_pagination_is_handled_by_livewire(): void
    {
        $admin = $this->user();

        foreach (range(1, 26) as $id) {
            $this->record($admin, subjectId: (string) $id, subjectLabel: "Evento {$id}");
        }

        Livewire::actingAs($admin);

        $component = Livewire::test(AuditEventList::class);

        $this->assertSame(25, substr_count($component->html(), 'id="evento-auditoria-'));

        $component
            ->call('gotoPage', 2)
            ->assertSet('paginators.page', 2);

        $this->assertSame(1, substr_count($component->html(), 'id="evento-auditoria-'));
    }

    public function test_query_string_configuration_and_state_restoration(): void
    {
        $admin = $this->user();
        $event = $this->record($admin, action: AuditAction::Updated, subjectId: '73', subjectLabel: 'Restaurado pela URL');

        Livewire::actingAs($admin);

        $component = Livewire::withQueryParams([
            'acao' => AuditAction::Updated->value,
            'entidade' => AuditEntity::Categoria->value,
            'entidade_id' => $event->subject_id,
            'page' => 1,
        ])->test(AuditEventList::class);

        $component
            ->assertSet('acao', AuditAction::Updated->value)
            ->assertSet('entidade', AuditEntity::Categoria->value)
            ->assertSet('entidade_id', $event->subject_id)
            ->assertSee('Restaurado pela URL');

        $this->assertArrayHasKey('acao', $component->effects['url']);
        $this->assertArrayHasKey('entidade', $component->effects['url']);
        $this->assertArrayHasKey('entidade_id', $component->effects['url']);
        $this->assertArrayHasKey('paginators.page', $component->effects['url']);
    }

    public function test_empty_filtered_result_and_detail_link_are_preserved(): void
    {
        $admin = $this->user();
        $event = $this->record($admin, subjectLabel: 'Com link de detalhes');

        Livewire::actingAs($admin);

        Livewire::test(AuditEventList::class)
            ->set('acao', AuditAction::Created->value)
            ->assertSee(route('admin.auditoria.show', $event), false)
            ->assertSee('acao=created', false)
            ->set('acao', AuditAction::Deleted->value)
            ->assertSee('Nenhum evento encontrado')
            ->assertDontSee('Com link de detalhes');
    }

    public function test_invalid_livewire_filter_is_rejected(): void
    {
        $admin = $this->user();

        Livewire::actingAs($admin);

        Livewire::test(AuditEventList::class)
            ->set('acao', 'invalid')
            ->assertHasErrors('acao');
    }

    private function user(string $role = 'admin'): User
    {
        return User::factory()->create([
            'role' => $role,
            'status' => 'ativo',
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
        CarbonImmutable::setTestNow(CarbonImmutable::parse($occurredAt, 'UTC'));
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
}
