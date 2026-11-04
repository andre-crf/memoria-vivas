<?php

namespace Tests\Feature;

use App\Auditing\AuditContext;
use App\Auditing\AuditEventData;
use App\Auditing\AuditRecorder;
use App\Auditing\Enums\AuditAction;
use App\Auditing\Enums\AuditEntity;
use App\Auditing\Enums\AuditSource;
use App\Livewire\Admin\AuditEventList;
use App\Models\Categoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class PaginacaoAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_pagination_is_in_portuguese_and_has_no_dark_mode(): void
    {
        for ($i = 1; $i <= 17; $i++) {
            Categoria::create(['titulo' => sprintf('Categoria %02d', $i)]);
        }

        $html = $this->actingAs(User::factory()->create(['role' => 'admin', 'status' => 'ativo']))
            ->get(route('admin.categorias.index', ['q' => 'Categoria']))
            ->assertOk()
            ->assertSeeInOrder(['Mostrando', '1', 'a', '15', 'de', '17', 'resultados'])
            ->assertSee('aria-label="Paginação"', false)
            ->assertSee('Próxima')
            ->assertSee('aria-current="page"', false)
            ->assertSee('q=Categoria&amp;page=2', false)
            ->assertDontSee('Showing')
            ->assertDontSee('results')
            ->getContent();

        $this->assertStringNotContainsString('dark:', $html);
    }

    public function test_first_and_last_pages_disable_the_matching_controls(): void
    {
        $paginador = fn (int $pagina) => new LengthAwarePaginator(range(1, 2), 4, 2, $pagina, ['path' => '/lista']);

        $primeira = (string) $paginador(1)->links();
        $this->assertStringContainsString('aria-label="Página anterior"', $primeira);
        $this->assertMatchesRegularExpression('/aria-disabled="true"\s+aria-label="Página anterior"/', $primeira);
        $this->assertStringContainsString('href="/lista?page=2"', $primeira);

        $ultima = (string) $paginador(2)->links();
        $this->assertMatchesRegularExpression('/aria-disabled="true"\s+aria-label="Próxima página"/', $ultima);
        $this->assertStringContainsString('href="/lista?page=1"', $ultima);
    }

    public function test_livewire_pagination_uses_the_same_view_and_changes_page_without_links(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'ativo']);

        foreach (range(1, 30) as $indice) {
            app(AuditRecorder::class)->record(
                AuditContext::forUser($admin, AuditSource::Web, (string) Str::uuid()),
                new AuditEventData(
                    action: AuditAction::Created,
                    subjectType: AuditEntity::Categoria,
                    subjectId: (string) $indice,
                    subjectLabel: "Evento {$indice}",
                    newValues: ['titulo' => "Evento {$indice}"],
                ),
            );
        }

        Livewire::actingAs($admin)->test(AuditEventList::class)
            ->assertSeeHtml('aria-label="Paginação"')
            ->assertSee('Mostrando')
            ->assertSee('resultados')
            ->assertSeeHtml("wire:click=\"gotoPage(2, 'page')\"")
            ->assertSeeHtml("wire:click=\"nextPage('page')\"")
            ->assertDontSee('Showing')
            ->assertDontSeeHtml('dark:')
            ->call('gotoPage', 2)
            ->assertSeeHtml("wire:click=\"previousPage('page')\"");
    }
}
