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
use App\Support\DataExibicao;
use App\Support\FusoDoUsuario;
use Carbon\CarbonImmutable;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Override;
use Tests\TestCase;

/**
 * Os instantes são sempre gravados em UTC; estes testes verificam que a
 * exibição e o recorte de período acontecem no fuso de quem está vendo.
 *
 * `2026-09-25 12:00:00` em UTC equivale a 09:00 em America/Sao_Paulo (UTC−3)
 * e a 08:00 em America/Manaus (UTC−4).
 */
class AuditoriaDataHoraExibicaoTest extends TestCase
{
    use RefreshDatabase;

    #[Override]
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_occurrence_is_listed_in_the_display_timezone(): void
    {
        $admin = $this->user();
        $this->record($admin);

        $this->actingAs($admin)
            ->get(route('admin.auditoria.index'))
            ->assertOk()
            ->assertSee('25/09/2026 09:00:00')
            ->assertDontSee('25/09/2026 12:00:00');
    }

    public function test_time_element_keeps_the_instant_in_utc(): void
    {
        $admin = $this->user();
        $this->record($admin);

        $this->actingAs($admin)
            ->get(route('admin.auditoria.index'))
            ->assertOk()
            ->assertSee('datetime="2026-09-25T12:00:00+00:00"', false)
            ->assertSee('data-data-hora', false);
    }

    /**
     * O cookie é escrito por `document.cookie`, em texto puro, e por isso os
     * testes usam `withUnencryptedCookie()`: `withCookie()` criptografa o
     * valor e exercitaria um cenário que o navegador nunca produz.
     */
    public function test_browser_timezone_cookie_changes_the_text_but_not_the_instant(): void
    {
        $admin = $this->user();
        $this->record($admin);

        $this->actingAs($admin)
            ->withUnencryptedCookie(FusoDoUsuario::COOKIE, 'America/Manaus')
            ->get(route('admin.auditoria.index'))
            ->assertOk()
            ->assertSee('25/09/2026 08:00:00')
            ->assertDontSee('25/09/2026 09:00:00')
            ->assertSee('datetime="2026-09-25T12:00:00+00:00"', false);
    }

    /**
     * `document.cookie` grava o identificador percent-encoded, já que a barra
     * é escapada por `encodeURIComponent`.
     */
    public function test_percent_encoded_cookie_is_understood(): void
    {
        $admin = $this->user();
        $this->record($admin);

        $this->actingAs($admin)
            ->withUnencryptedCookie(FusoDoUsuario::COOKIE, 'America%2FManaus')
            ->get(route('admin.auditoria.index'))
            ->assertOk()
            ->assertSee('25/09/2026 08:00:00');
    }

    /**
     * Requisições seguidas não podem herdar o fuso uma da outra: cada uma
     * resolve o seu a partir do que recebeu.
     */
    public function test_consecutive_requests_use_their_own_timezone(): void
    {
        $admin = $this->user();
        $this->record($admin);

        $this->actingAs($admin)
            ->withUnencryptedCookie(FusoDoUsuario::COOKIE, 'America/Manaus')
            ->get(route('admin.auditoria.index'))
            ->assertOk()
            ->assertSee('25/09/2026 08:00:00');

        $this->actingAs($admin)
            ->withUnencryptedCookie(FusoDoUsuario::COOKIE, 'Europe/Lisbon')
            ->get(route('admin.auditoria.index'))
            ->assertOk()
            ->assertSee('25/09/2026 13:00:00')
            ->assertDontSee('25/09/2026 08:00:00');

        // O caso sem cookie algum é coberto por
        // test_occurrence_is_listed_in_the_display_timezone; aqui não dá para
        // exercitá-lo porque o helper de teste mantém o cookie entre as
        // requisições do mesmo método.
    }

    /**
     * O cookie trafega sem criptografia, então seu conteúdo é dado do cliente
     * e precisa ser tratado como tal.
     */
    public function test_invalid_or_tampered_cookie_falls_back_to_the_configured_timezone(): void
    {
        $admin = $this->user();
        $this->record($admin);

        $valores = [
            '-03:00',                    // offset fixo, proibido por decisão
            'GMT-3',                     // apelido fora da lista IANA
            'Fuso/Inexistente',          // identificador inventado
            '',                          // cookie vazio
            'America/Sao_Paulo; DROP',   // valor manipulado
            str_repeat('x', 500),        // valor absurdo
        ];

        foreach ($valores as $fuso) {
            $this->actingAs($admin)
                ->withUnencryptedCookie(FusoDoUsuario::COOKIE, $fuso)
                ->get(route('admin.auditoria.index'))
                ->assertOk()
                ->assertSee('25/09/2026 09:00:00');
        }
    }

    /**
     * Garante que a exceção de criptografia continua declarada: sem ela o
     * cookie do navegador é descartado em silêncio e tudo recai no fallback.
     */
    public function test_timezone_cookie_is_exempt_from_encryption(): void
    {
        $this->assertTrue(
            app(EncryptCookies::class)->isDisabled(FusoDoUsuario::COOKIE),
            'O cookie de fuso precisa estar dispensado da criptografia em bootstrap/app.php.',
        );
    }

    public function test_display_timezone_requires_an_iana_identifier(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new DataExibicao('-03:00');
    }

    public function test_snapshot_dates_are_formatted_in_the_display_timezone(): void
    {
        $admin = $this->user();
        $event = $this->record(
            $admin,
            action: AuditAction::Deleted,
            entity: AuditEntity::ItemAcervo,
            oldValues: ['deleted_at' => '2026-09-25T12:00:00+00:00'],
            newValues: null,
        );

        $this->actingAs($admin)
            ->get(route('admin.auditoria.show', $event))
            ->assertOk()
            ->assertSee('Excluído em')
            // A célula traz a data formatada, não mais o ISO cru do snapshot.
            ->assertSee('>25/09/2026 09:00:00<', false)
            ->assertDontSee('>2026-09-25T12:00:00+00:00<', false);
    }

    public function test_snapshot_dates_inside_lists_are_formatted(): void
    {
        $admin = $this->user();
        $event = $this->record(
            $admin,
            newValues: ['marcos' => ['2026-09-25T12:00:00+00:00', '2026-09-26T12:00:00+00:00']],
        );

        $this->actingAs($admin)
            ->get(route('admin.auditoria.show', $event))
            ->assertOk()
            ->assertSee('25/09/2026 09:00:00, 26/09/2026 09:00:00');
    }

    public function test_free_text_mentioning_a_date_is_preserved(): void
    {
        $admin = $this->user();
        $event = $this->record($admin, newValues: [
            'observacao' => 'Arquivo recebido em 2026-09-25T12:00:00+00:00 pelo cedente.',
            'sha256' => 'f7a1b2c3',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.auditoria.show', $event))
            ->assertOk()
            ->assertSee('Arquivo recebido em 2026-09-25T12:00:00+00:00 pelo cedente.')
            ->assertSee('f7a1b2c3');
    }

    public function test_photograph_panels_show_timestamps_in_the_display_timezone(): void
    {
        $admin = $this->user();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-25 12:00:00', 'UTC'));
        $fotografia = ItemAcervo::create([
            'titulo' => 'Praça central',
            'tipo_item' => 'fotografia',
            'tipo_data' => 'desconhecida',
            'estado_conservacao' => 'desconhecido',
            'status' => 'rascunho',
            'visibilidade' => Visibilidade::Privado,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.fotografias.show', $fotografia))
            ->assertOk()
            // Formato curto: a data de criação do item não exibe segundos.
            ->assertSee('25/09/2026 09:00')
            ->assertDontSee('25/09/2026 12:00');

        $fotografia->delete();

        $this->actingAs($admin)
            ->get(route('admin.fotografias.trashed'))
            ->assertOk()
            ->assertSee('25/09/2026 09:00')
            ->assertSee('datetime="2026-09-25T12:00:00+00:00"', false);
    }

    public function test_photograph_panels_keep_the_empty_state_without_a_time_element(): void
    {
        $admin = $this->user();
        $fotografia = ItemAcervo::create([
            'titulo' => 'Sem data de alteração',
            'tipo_item' => 'fotografia',
            'tipo_data' => 'desconhecida',
            'estado_conservacao' => 'desconhecido',
            'status' => 'rascunho',
            'visibilidade' => Visibilidade::Privado,
        ]);
        $fotografia->timestamps = false;
        $fotografia->forceFill(['created_at' => null, 'updated_at' => null])->save();

        $this->actingAs($admin)
            ->get(route('admin.fotografias.show', $fotografia))
            ->assertOk()
            ->assertSee('Não registrado')
            ->assertSee('Não registrada');
    }

    public function test_period_filter_follows_the_timezone_used_on_screen(): void
    {
        $admin = $this->user();
        // 20/09 00:00 e 21/09 23:59:59 em America/Sao_Paulo.
        $this->record($admin, occurredAt: '2026-09-20 03:00:00', subjectLabel: 'Início em Sao Paulo');
        $this->record($admin, occurredAt: '2026-09-22 02:59:59', subjectLabel: 'Fim em Sao Paulo');
        // Fora do período em Sao Paulo, mas dentro dele em Manaus (UTC-4).
        $this->record($admin, occurredAt: '2026-09-22 03:30:00', subjectLabel: 'Somente em Manaus');

        $periodo = ['data_inicio' => '2026-09-20', 'data_fim' => '2026-09-21'];

        $this->actingAs($admin)
            ->get(route('admin.auditoria.index', $periodo))
            ->assertOk()
            ->assertSee('Início em Sao Paulo')
            ->assertSee('Fim em Sao Paulo')
            ->assertDontSee('Somente em Manaus')
            ->assertSee('America/Sao_Paulo');

        $this->actingAs($admin)
            ->get(route('admin.auditoria.index', [...$periodo, 'fuso' => 'America/Manaus']))
            ->assertOk()
            ->assertSee('Somente em Manaus')
            ->assertSee('America/Manaus')
            // 20/09 00:00 em Sao Paulo ainda é 19/09 23:00 em Manaus.
            ->assertDontSee('Início em Sao Paulo');
    }

    public function test_request_timezone_takes_precedence_over_the_cookie(): void
    {
        $admin = $this->user();
        $this->record($admin);

        $this->actingAs($admin)
            ->withUnencryptedCookie(FusoDoUsuario::COOKIE, 'America/Sao_Paulo')
            ->get(route('admin.auditoria.index', ['fuso' => 'America/Manaus']))
            ->assertOk()
            ->assertSee('25/09/2026 08:00:00');
    }

    public function test_invalid_filter_timezone_is_rejected(): void
    {
        $admin = $this->user();

        $this->actingAs($admin)
            ->from(route('admin.auditoria.index'))
            ->get(route('admin.auditoria.index', ['fuso' => '-03:00']))
            ->assertRedirect(route('admin.auditoria.index'))
            ->assertSessionHasErrors('fuso');
    }

    public function test_events_are_persisted_in_utc(): void
    {
        $admin = $this->user();
        $this->record($admin);

        $this->assertSame(
            '2026-09-25 12:00:00',
            (string) DB::table('audit_events')->orderByDesc('id')->value('occurred_at'),
        );
    }

    /**
     * A conexão real usa MySQL, onde colunas TIMESTAMP são convertidas pelo
     * fuso da sessão. A suíte roda em SQLite, que não tem fuso de sessão, então
     * aqui só é possível fixar a configuração que garante a leitura em UTC.
     */
    public function test_database_connections_are_pinned_to_utc(): void
    {
        $this->assertSame('+00:00', config('database.connections.mysql.timezone'));
        $this->assertSame('+00:00', config('database.connections.mariadb.timezone'));
        $this->assertSame('UTC', config('app.timezone'));
    }

    private function user(string $role = 'admin'): User
    {
        return User::factory()->create([
            'nome' => 'Administrador',
            'role' => $role,
            'status' => 'ativo',
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    private function record(
        User $actor,
        string $occurredAt = '2026-09-25 12:00:00',
        AuditAction $action = AuditAction::Created,
        AuditEntity $entity = AuditEntity::Categoria,
        string $subjectLabel = 'Registro auditado',
        ?array $oldValues = null,
        ?array $newValues = ['titulo' => 'Registro auditado'],
    ): AuditEvent {
        CarbonImmutable::setTestNow(CarbonImmutable::parse($occurredAt, 'UTC'));

        return app(AuditRecorder::class)->record(
            AuditContext::forUser($actor, AuditSource::Web, (string) Str::uuid()),
            new AuditEventData(
                action: $action,
                subjectType: $entity,
                subjectId: '1',
                subjectLabel: $subjectLabel,
                oldValues: $oldValues,
                newValues: $newValues,
            ),
        );
    }
}
