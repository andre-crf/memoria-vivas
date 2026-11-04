<?php

namespace Tests\Feature;

use App\Auditing\AuditContext;
use App\Auditing\AuditEventData;
use App\Auditing\AuditRecorder;
use App\Auditing\Enums\AuditAction;
use App\Auditing\Enums\AuditEntity;
use App\Auditing\Enums\AuditSource;
use App\Enums\Visibilidade;
use App\Models\AuditEvent;
use App\Models\ItemAcervo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminAuditoriaDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_administrators_can_view_event_details(): void
    {
        $admin = $this->user('admin');
        $operator = $this->user('operador');
        $event = $this->record($admin);

        $this->get(route('admin.auditoria.show', $event))
            ->assertRedirect(route('login'));

        $this->actingAs($operator)
            ->get(route('admin.auditoria.show', $event))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('admin.auditoria.show', $event))
            ->assertOk()
            ->assertSee('Evento de auditoria')
            ->assertSee('aria-current="page"', false);

        $this->actingAs($admin)
            ->get(route('admin.auditoria.show', 999999))
            ->assertNotFound();
    }

    public function test_details_use_historical_snapshots_and_translate_known_fields_and_values(): void
    {
        $admin = $this->user('admin', 'Nome histórico');
        $event = $this->record(
            actor: $admin,
            action: AuditAction::Updated,
            entity: AuditEntity::ItemAcervo,
            subjectId: '35',
            subjectLabel: 'Praça histórica',
            oldValues: [
                'titulo' => 'Praça antiga',
                'status' => 'rascunho',
                'visibilidade' => 'privado',
                'autor' => null,
            ],
            newValues: [
                'titulo' => 'Praça restaurada',
                'status' => 'publicado',
                'visibilidade' => 'publico',
                'autor' => ['id' => 8, 'label' => 'Foto Estrela'],
            ],
            metadata: [
                'operation' => 'acervo_item_update',
                'change_groups' => ['descriptive', 'publication'],
            ],
        );
        $admin->update(['nome' => 'Nome atual']);

        $this->actingAs($admin)
            ->get(route('admin.auditoria.show', $event))
            ->assertOk()
            ->assertSee('Nome histórico')
            ->assertSee('Administrador')
            ->assertSee('Praça histórica')
            ->assertSee('Item do acervo · #35')
            ->assertSee('Interface web')
            ->assertSee('Título')
            ->assertSee('Praça antiga')
            ->assertSee('Praça restaurada')
            ->assertSee('Rascunho')
            ->assertSee('Publicado')
            ->assertSee('Privado')
            ->assertSee('Público')
            ->assertSee('Não informado')
            ->assertSee('Foto Estrela (#8)')
            ->assertSee('Alteração de item do acervo')
            ->assertSee('Dados descritivos, Publicação');
    }

    public function test_details_distinguish_missing_values_from_null_and_escape_historical_content(): void
    {
        $admin = $this->user();
        $event = $this->record(
            actor: $admin,
            action: AuditAction::Created,
            oldValues: null,
            newValues: [
                'titulo' => '<script>alert("audit")</script>',
                'descricao' => null,
            ],
            metadata: null,
        );

        $this->actingAs($admin)
            ->get(route('admin.auditoria.show', $event))
            ->assertOk()
            ->assertSee('Não se aplica')
            ->assertSee('Não informado')
            ->assertSee('&lt;script&gt;alert(&quot;audit&quot;)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert("audit")</script>', false)
            ->assertSee('Nenhum metadado adicional foi registrado.');
    }

    public function test_event_without_payload_has_an_explanatory_state(): void
    {
        $admin = $this->user();
        $event = $this->record(
            actor: $admin,
            action: AuditAction::PasswordChanged,
            entity: AuditEntity::User,
            oldValues: null,
            newValues: null,
            metadata: ['operation' => 'self_password_change'],
        );

        $this->actingAs($admin)
            ->get(route('admin.auditoria.show', $event))
            ->assertOk()
            ->assertSee('Este evento não possui valores anteriores ou novos.')
            ->assertSee('Alteração da própria senha');
    }

    public function test_correlated_events_are_isolated_paginated_and_keep_list_navigation(): void
    {
        $admin = $this->user();
        $correlationId = (string) Str::uuid();
        $event = $this->record($admin, correlationId: $correlationId, subjectLabel: 'Evento principal');

        foreach (range(1, 11) as $number) {
            $this->record(
                actor: $admin,
                correlationId: $correlationId,
                subjectId: (string) ($number + 100),
                subjectLabel: "Relacionado {$number}",
            );
        }

        $unrelated = $this->record($admin, subjectLabel: 'Evento não relacionado');

        $response = $this->actingAs($admin)->get(route('admin.auditoria.show', [
            'evento' => $event,
            'acao' => AuditAction::Created->value,
            'page' => 2,
        ]));

        $response
            ->assertOk()
            ->assertSee('Eventos correlacionados')
            ->assertSee('correlacionados=2', false)
            ->assertSee('acao=created', false)
            ->assertSee('page=2', false)
            ->assertDontSee("evento-correlacionado-{$unrelated->id}", false);
        $this->assertSame(10, substr_count($response->getContent(), 'id="evento-correlacionado-'));
    }

    public function test_photograph_history_is_admin_only_and_includes_item_and_file_events(): void
    {
        $admin = $this->user('admin');
        $operator = $this->user('operador');
        $photograph = $this->photograph('Fotografia auditada');
        $otherPhotograph = $this->photograph('Outra fotografia');
        $itemEvent = $this->record(
            actor: $admin,
            entity: AuditEntity::ItemAcervo,
            subjectId: (string) $photograph->id,
            subjectLabel: 'Evento do item',
        );
        $fileEvent = $this->record(
            actor: $admin,
            action: AuditAction::Uploaded,
            entity: AuditEntity::Arquivo,
            subjectId: '88',
            subjectLabel: 'foto-original.jpg',
            metadata: ['item_acervo' => ['id' => $photograph->id, 'label' => $photograph->titulo]],
        );
        $unrelated = $this->record(
            actor: $admin,
            entity: AuditEntity::ItemAcervo,
            subjectId: (string) $otherPhotograph->id,
            subjectLabel: 'Evento de outro item',
        );

        $this->actingAs($admin)
            ->get(route('admin.fotografias.show', $photograph))
            ->assertOk()
            ->assertSee('Histórico de auditoria')
            ->assertSee('Evento do item')
            ->assertSee('foto-original.jpg')
            ->assertSee(route('admin.auditoria.show', $itemEvent))
            ->assertSee(route('admin.auditoria.show', $fileEvent))
            ->assertDontSee('Evento de outro item')
            ->assertDontSee(route('admin.auditoria.show', $unrelated));

        $this->actingAs($operator)
            ->get(route('admin.fotografias.show', $photograph))
            ->assertOk()
            ->assertDontSee('Histórico de auditoria')
            ->assertDontSee('Evento do item')
            ->assertDontSee('foto-original.jpg');
    }

    public function test_detail_queries_do_not_create_or_modify_events(): void
    {
        $admin = $this->user();
        $event = $this->record($admin);
        $before = $event->getAttributes();

        $this->actingAs($admin)
            ->get(route('admin.auditoria.show', $event))
            ->assertOk();

        $this->assertDatabaseCount('audit_events', 1);
        $this->assertSame($before, $event->fresh()->getAttributes());
    }

    private function user(string $role = 'admin', string $name = 'Administrador'): User
    {
        return User::factory()->create([
            'nome' => $name,
            'role' => $role,
            'status' => 'ativo',
        ]);
    }

    private function photograph(string $title): ItemAcervo
    {
        return ItemAcervo::create([
            'titulo' => $title,
            'tipo_item' => 'fotografia',
            'tipo_data' => 'desconhecida',
            'estado_conservacao' => 'desconhecido',
            'status' => 'rascunho',
            'visibilidade' => Visibilidade::Privado,
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     * @param  array<string, mixed>|null  $metadata
     */
    private function record(
        ?User $actor,
        AuditAction $action = AuditAction::Created,
        AuditEntity $entity = AuditEntity::Categoria,
        string $subjectId = '1',
        string $subjectLabel = 'Registro auditado',
        ?array $oldValues = null,
        ?array $newValues = ['titulo' => 'Registro auditado'],
        ?array $metadata = null,
        ?string $correlationId = null,
    ): AuditEvent {
        $requestId = (string) Str::uuid();
        $context = $actor instanceof User
            ? AuditContext::forUser($actor, AuditSource::Web, $requestId, $correlationId)
            : AuditContext::forSystem(AuditSource::System, $requestId, $correlationId);

        return app(AuditRecorder::class)->record(
            $context,
            new AuditEventData(
                action: $action,
                subjectType: $entity,
                subjectId: $subjectId,
                subjectLabel: $subjectLabel,
                oldValues: $oldValues,
                newValues: $newValues,
                metadata: $metadata,
            ),
        );
    }
}
