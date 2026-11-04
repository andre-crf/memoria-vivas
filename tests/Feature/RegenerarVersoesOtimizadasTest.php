<?php

namespace Tests\Feature;

use App\Auditing\AuditContext;
use App\Auditing\Enums\AuditAction;
use App\Auditing\Enums\AuditSource;
use App\Enums\Visibilidade;
use App\Models\Arquivo;
use App\Models\AuditEvent;
use App\Models\ItemAcervo;
use App\Models\User;
use App\Services\Arquivos\Contracts\ArquivoStorage;
use App\Services\Arquivos\LaravelArquivoStorage;
use App\Services\Arquivos\RegenerarVersoesOtimizadas;
use App\Services\Arquivos\StorageMutationJournal;
use App\Support\OptimizedImageVersions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use JsonException;
use RuntimeException;
use Tests\TestCase;

class RegenerarVersoesOtimizadasTest extends TestCase
{
    use RefreshDatabase;

    public function test_regeneration_replaces_every_derivative_and_preserves_the_original(): void
    {
        Storage::fake('local');
        $admin = $this->internalUser();
        $fotografia = $this->photographWithOriginal($admin);
        $original = $this->original($fotografia);
        $conteudoOriginal = Storage::disk('local')->get($original->storage_path);
        $antigas = $this->derivatives($fotografia);

        $this->actingAs($admin)
            ->post(route('admin.fotografias.regenerate-versions', $fotografia))
            ->assertRedirect(route('admin.fotografias.show', $fotografia))
            ->assertSessionHas('success', 'Versões otimizadas regeneradas com sucesso.');

        $novas = $this->derivatives($fotografia);
        $originalDepois = $this->original($fotografia);

        $this->assertSame($original->only(['id', 'storage_path', 'sha256', 'file_size']), $originalDepois->only(['id', 'storage_path', 'sha256', 'file_size']));
        $this->assertSame($conteudoOriginal, Storage::disk('local')->get($original->storage_path));
        $this->assertSame(['large', 'medium', 'thumbnail'], $novas->keys()->sort()->values()->all());
        $this->assertEmpty(array_intersect($antigas->pluck('id')->all(), $novas->pluck('id')->all()));

        foreach ($antigas as $antiga) {
            Storage::disk('local')->assertMissing($antiga->storage_path);
        }

        $this->assertDerivativeFilesMatchRecords($fotografia);

        $evento = AuditEvent::query()->where('action', AuditAction::Regenerated)->sole();
        $this->assertSame((string) $original->id, $evento->subject_id);
        $this->assertSame($admin->id, $evento->actor_user_id);
        $this->assertSame('arquivo_derivations_regenerate', $evento->metadata['operation']);
        $this->assertSame('todas', $evento->metadata['mode']);
        $this->assertCount(3, $evento->metadata['derivations']['generated']);
        $this->assertSame([], $evento->metadata['derivations']['failed_versions']);
        $this->assertEqualsCanonicalizing($antigas->pluck('id')->all(), array_column($evento->metadata['derivations_removed'], 'id'));
    }

    public function test_missing_only_mode_generates_only_the_absent_version(): void
    {
        Storage::fake('local');
        $admin = $this->internalUser();
        $fotografia = $this->photographWithOriginal($admin);
        $antigas = $this->derivatives($fotografia);
        Storage::disk('local')->delete($antigas['thumbnail']->storage_path);
        $antigas['thumbnail']->delete();

        $resultado = app(RegenerarVersoesOtimizadas::class)->execute(
            actor: $admin,
            item: $fotografia,
            context: $this->context($admin),
            somenteAusentes: true,
        );

        $novas = $this->derivatives($fotografia);

        $this->assertSame(['thumbnail'], collect($resultado->generated)->pluck('versao_arquivo')->all());
        $this->assertSame($antigas['large']->id, $novas['large']->id);
        $this->assertSame($antigas['medium']->id, $novas['medium']->id);
        $this->assertDerivativeFilesMatchRecords($fotografia);
        $this->assertSame('ausentes', AuditEvent::query()->where('action', AuditAction::Regenerated)->sole()->metadata['mode']);
    }

    public function test_missing_only_mode_does_nothing_when_all_versions_exist(): void
    {
        Storage::fake('local');
        $admin = $this->internalUser();
        $fotografia = $this->photographWithOriginal($admin);
        $antigas = $this->derivatives($fotografia);

        $resultado = app(RegenerarVersoesOtimizadas::class)->execute($admin, $fotografia, $this->context($admin), somenteAusentes: true);

        $this->assertTrue($resultado->applicable);
        $this->assertSame([], $resultado->generated);
        $this->assertSame($antigas->pluck('id')->all(), $this->derivatives($fotografia)->pluck('id')->all());
        $this->assertSame(0, AuditEvent::query()->where('action', AuditAction::Regenerated)->count());
    }

    public function test_failed_version_keeps_its_previous_derivative(): void
    {
        Storage::fake('local');
        $admin = $this->internalUser();
        $fotografia = $this->photographWithOriginal($admin);
        $antigas = $this->derivatives($fotografia);
        $this->app->bind(
            ArquivoStorage::class,
            fn (): ArquivoStorage => new FailingVersionStorage(new LaravelArquivoStorage, 'medium'),
        );

        $this->actingAs($admin)
            ->post(route('admin.fotografias.regenerate-versions', $fotografia))
            ->assertSessionHas('warning', 'Versões regeneradas, exceto Média. As versões anteriores dessas foram mantidas.');

        $novas = $this->derivatives($fotografia);

        $this->assertSame($antigas['medium']->id, $novas['medium']->id);
        Storage::disk('local')->assertExists($antigas['medium']->storage_path);
        $this->assertNotSame($antigas['large']->id, $novas['large']->id);
        $this->assertNotSame($antigas['thumbnail']->id, $novas['thumbnail']->id);
        $this->assertDerivativeFilesMatchRecords($fotografia);
        $this->assertSame(['medium'], AuditEvent::query()->where('action', AuditAction::Regenerated)->sole()->metadata['derivations']['failed_versions']);
    }

    public function test_when_every_version_fails_the_previous_derivatives_stay_untouched(): void
    {
        Storage::fake('local');
        $admin = $this->internalUser();
        $fotografia = $this->photographWithOriginal($admin);
        $antigas = $this->derivatives($fotografia);
        $this->app->bind(
            ArquivoStorage::class,
            fn (): ArquivoStorage => new FailingVersionStorage(new LaravelArquivoStorage, null),
        );

        $this->actingAs($admin)
            ->post(route('admin.fotografias.regenerate-versions', $fotografia))
            ->assertSessionHas('error', 'Não foi possível regenerar as versões (Miniatura, Média e Grande). As versões anteriores foram mantidas.');

        $this->assertSame($antigas->pluck('id')->all(), $this->derivatives($fotografia)->pluck('id')->all());
        $this->assertDerivativeFilesMatchRecords($fotografia);
    }

    public function test_feedback_distinguishes_complete_partial_and_total_failure(): void
    {
        Storage::fake('local');
        $admin = $this->internalUser();
        $fotografia = $this->photographWithOriginal($admin);
        $regenerar = fn () => $this->actingAs($admin)
            ->followingRedirects()
            ->post(route('admin.fotografias.regenerate-versions', $fotografia))
            ->assertOk();

        $regenerar()
            ->assertSee('data-toast-type="success"', false)
            ->assertDontSee('data-toast-type="warning"', false)
            ->assertDontSee('data-toast-type="error"', false);

        $this->app->bind(ArquivoStorage::class, fn (): ArquivoStorage => new FailingVersionStorage(new LaravelArquivoStorage, 'medium'));
        $regenerar()
            ->assertSee('data-toast-type="warning"', false)
            ->assertSee('Atenção:')
            ->assertDontSee('data-toast-type="success"', false);

        $this->app->bind(ArquivoStorage::class, fn (): ArquivoStorage => new FailingVersionStorage(new LaravelArquivoStorage, null));
        $regenerar()
            ->assertSee('data-toast-type="error"', false)
            ->assertSee('Erro:')
            ->assertDontSee('data-toast-type="success"', false);
    }

    public function test_audit_failure_rolls_back_records_and_removes_only_the_new_files(): void
    {
        Storage::fake('local');
        $admin = $this->internalUser();
        $fotografia = $this->photographWithOriginal($admin);
        $antigas = $this->derivatives($fotografia);
        // Título com UTF-8 inválido faz a gravação da auditoria falhar dentro da transação.
        DB::table('item_acervos')->where('id', $fotografia->id)->update(['titulo' => "Inválida \xB1"]);

        try {
            app(RegenerarVersoesOtimizadas::class)->execute($admin, $fotografia->fresh(), $this->context($admin));
            $this->fail('A gravação da auditoria deveria rejeitar JSON inválido.');
        } catch (JsonException) {
            // Os arquivos novos são removidos depois do rollback.
        }

        $this->assertSame($antigas->pluck('id')->all(), $this->derivatives($fotografia)->pluck('id')->all());
        $this->assertDerivativeFilesMatchRecords($fotografia);
        $this->assertSame(0, AuditEvent::query()->where('action', AuditAction::Regenerated)->count());
    }

    public function test_original_replaced_during_rendering_aborts_without_touching_records(): void
    {
        Storage::fake('local');
        $admin = $this->internalUser();
        $fotografia = $this->photographWithOriginal($admin);
        $antigas = $this->derivatives($fotografia);
        $this->app->bind(OptimizedImageVersions::class, ConcurrentReplacementVersions::class);

        try {
            app(RegenerarVersoesOtimizadas::class)->execute($admin, $fotografia, $this->context($admin));
            $this->fail('A regeneração deveria detectar a troca do original.');
        } catch (RuntimeException $exception) {
            $this->assertSame('O arquivo original mudou durante a regeneração das versões.', $exception->getMessage());
        }

        $this->assertSame($antigas->pluck('id')->all(), $this->derivatives($fotografia)->pluck('id')->all());
        $this->assertDerivativeFilesMatchRecords($fotografia);
    }

    public function test_non_image_original_has_its_versions_dispensed(): void
    {
        Storage::fake('local');
        $admin = $this->internalUser();
        $fotografia = $this->photographWithOriginal(
            $admin,
            UploadedFile::fake()->create('documento.pdf', 20, 'application/pdf'),
        );

        $this->actingAs($admin)
            ->post(route('admin.fotografias.regenerate-versions', $fotografia))
            ->assertSessionHas('warning', 'O arquivo original não é uma imagem; versões otimizadas não se aplicam.');

        $this->assertSame(1, $fotografia->arquivos()->count());
        $this->assertSame(0, AuditEvent::query()->where('action', AuditAction::Regenerated)->count());

        $this->actingAs($admin)
            ->get(route('admin.fotografias.show', $fotografia))
            ->assertOk()
            ->assertSee('Versões otimizadas dispensadas: o original não é uma imagem.')
            ->assertDontSee('Regenerar versões')
            ->assertDontSee('data-versao-ausente', false);
    }

    public function test_show_page_offers_regeneration_and_flags_missing_versions(): void
    {
        Storage::fake('local');
        $admin = $this->internalUser();
        $fotografia = $this->photographWithOriginal($admin);
        $this->derivatives($fotografia)['large']->delete();

        $this->actingAs($admin)
            ->get(route('admin.fotografias.show', $fotografia))
            ->assertOk()
            ->assertSee(route('admin.fotografias.regenerate-versions', $fotografia), false)
            ->assertSee('Regenerar versões')
            ->assertSee('data-versao-ausente="large"', false)
            ->assertSee('Ausente: a geração falhou ou não foi concluída')
            ->assertDontSee('data-versao-ausente="medium"', false);
    }

    public function test_regeneration_requires_an_internal_user_and_a_photograph(): void
    {
        Storage::fake('local');
        $admin = $this->internalUser();
        $fotografia = $this->photographWithOriginal($admin);
        $documento = ItemAcervo::create($this->itemAttributes(['tipo_item' => 'documento']));

        auth()->logout();
        $this->post(route('admin.fotografias.regenerate-versions', $fotografia))->assertRedirect(route('login'));
        $this->actingAs($admin)->post(route('admin.fotografias.regenerate-versions', $documento))->assertNotFound();
        $this->assertSame(0, AuditEvent::query()->where('action', AuditAction::Regenerated)->count());
    }

    public function test_command_requires_a_target(): void
    {
        $this->artisan('acervo:regenerar-versoes')
            ->expectsOutput('Informe os IDs das fotografias, --ausentes ou --todas.')
            ->assertExitCode(2);
    }

    public function test_command_regenerates_a_specific_photograph_as_the_system(): void
    {
        Storage::fake('local');
        $admin = $this->internalUser();
        $fotografia = $this->photographWithOriginal($admin);
        $outra = $this->photographWithOriginal($admin, UploadedFile::fake()->image('outra.jpg', 2200, 1500));
        $antigasDaOutra = $this->derivatives($outra);

        $this->artisan('acervo:regenerar-versoes', ['fotografia' => [$fotografia->id]])
            ->expectsOutputToContain("#{$fotografia->id} Praça central: thumbnail, medium, large")
            ->assertSuccessful();

        $evento = AuditEvent::query()->where('action', AuditAction::Regenerated)->sole();
        $this->assertNull($evento->actor_user_id);
        $this->assertSame(AuditSource::Console, $evento->source);
        $this->assertSame($antigasDaOutra->pluck('id')->all(), $this->derivatives($outra)->pluck('id')->all());
    }

    public function test_command_missing_mode_only_touches_incomplete_photographs(): void
    {
        Storage::fake('local');
        $admin = $this->internalUser();
        $completa = $this->photographWithOriginal($admin);
        $incompleta = $this->photographWithOriginal($admin, UploadedFile::fake()->image('incompleta.jpg', 2200, 1500));
        $semMiniatura = $this->derivatives($incompleta)['thumbnail'];
        Storage::disk('local')->delete($semMiniatura->storage_path);
        $semMiniatura->delete();
        $antigasDaCompleta = $this->derivatives($completa);

        $this->artisan('acervo:regenerar-versoes', ['--ausentes' => true])->assertSuccessful();

        $this->assertSame($antigasDaCompleta->pluck('id')->all(), $this->derivatives($completa)->pluck('id')->all());
        $this->assertArrayHasKey('thumbnail', $this->derivatives($incompleta)->all());
        $this->assertDerivativeFilesMatchRecords($incompleta);
        $this->assertSame((string) $this->original($incompleta)->id, AuditEvent::query()->where('action', AuditAction::Regenerated)->sole()->subject_id);
    }

    public function test_command_processes_every_image_photograph_in_batches(): void
    {
        Storage::fake('local');
        $admin = $this->internalUser();
        $fotografias = collect([[2400, 1600], [2300, 1500], [2200, 1400]])
            ->map(fn (array $dimensoes) => $this->photographWithOriginal($admin, UploadedFile::fake()->image("foto-{$dimensoes[0]}.jpg", ...$dimensoes)));
        $pdf = $this->photographWithOriginal($admin, UploadedFile::fake()->create('documento.pdf', 20, 'application/pdf'));

        $this->artisan('acervo:regenerar-versoes', ['--todas' => true, '--lote' => 1])
            ->expectsOutputToContain('3 fotografia(s) processada(s), 9 versão(ões) gerada(s), 0 com falha, 0 ignorada(s).')
            ->assertSuccessful();

        $eventos = AuditEvent::query()->where('action', AuditAction::Regenerated)->get();
        $this->assertCount(3, $eventos);
        $this->assertCount(1, $eventos->pluck('correlation_id')->unique());
        $this->assertCount(3, $eventos->pluck('request_id')->unique());
        $this->assertSame(1, $pdf->arquivos()->count());
        $fotografias->each(fn (ItemAcervo $fotografia) => $this->assertDerivativeFilesMatchRecords($fotografia));
    }

    public function test_command_reports_unknown_photographs_as_failure(): void
    {
        $this->artisan('acervo:regenerar-versoes', ['fotografia' => [999]])
            ->expectsOutput('#999: fotografia não encontrada ou sem original de imagem.')
            ->assertFailed();
    }

    private function photographWithOriginal(User $actor, ?UploadedFile $arquivo = null): ItemAcervo
    {
        $this->actingAs($actor)
            ->post(route('admin.fotografias.store'), $this->itemAttributes([
                'visibilidade' => Visibilidade::Privado->value,
                'arquivo_original' => $arquivo ?? UploadedFile::fake()->image('original.jpg', 2400, 1600),
            ]))
            ->assertSessionHasNoErrors();

        return ItemAcervo::query()->latest('id')->firstOrFail();
    }

    /** @return array<string, mixed> */
    private function itemAttributes(array $override = []): array
    {
        return $override + [
            'titulo' => 'Praça central',
            'tipo_item' => 'fotografia',
            'tipo_data' => 'ano',
            'ano' => 1980,
            'estado_conservacao' => 'bom',
            'status' => 'rascunho',
            'visibilidade' => Visibilidade::Privado,
        ];
    }

    private function original(ItemAcervo $fotografia): Arquivo
    {
        return $fotografia->arquivos()->where('versao_arquivo', 'original')->sole();
    }

    /** @return Collection<string, Arquivo> */
    private function derivatives(ItemAcervo $fotografia)
    {
        return $fotografia->arquivos()
            ->where('versao_arquivo', '!=', 'original')
            ->orderBy('versao_arquivo')
            ->get()
            ->keyBy('versao_arquivo');
    }

    /**
     * Cada registro derivado tem seu arquivo, e não sobra arquivo derivado sem registro.
     */
    private function assertDerivativeFilesMatchRecords(ItemAcervo $fotografia): void
    {
        $registros = $this->derivatives($fotografia)->pluck('storage_path')->sort()->values()->all();
        $arquivos = collect(Storage::disk('local')->files("acervo/derivados/{$fotografia->id}"))->sort()->values()->all();

        $this->assertSame($registros, $arquivos);
        Storage::disk('local')->assertExists($this->original($fotografia)->storage_path);
    }

    private function context(User $actor): AuditContext
    {
        return AuditContext::forUser($actor, AuditSource::Web, (string) Str::uuid());
    }

    private function internalUser(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'ativo']);
    }
}

/**
 * Falha ao gravar a versão informada (ou todas, com null), como um disco cheio faria.
 */
final readonly class FailingVersionStorage implements ArquivoStorage
{
    public function __construct(
        private ArquivoStorage $delegate,
        private ?string $version,
    ) {}

    public function storeUploaded(string $provider, UploadedFile $file, string $directory, string $filename): string
    {
        return $this->delegate->storeUploaded($provider, $file, $directory, $filename);
    }

    public function makeDirectory(string $provider, string $directory): void
    {
        $this->delegate->makeDirectory($provider, $directory);
    }

    public function absolutePath(string $provider, string $path): string
    {
        $alvo = $this->version === null ? 'acervo/derivados/' : "-{$this->version}.";

        if (str_contains($path, $alvo)) {
            throw new RuntimeException('Falha simulada ao gravar a versão.');
        }

        return $this->delegate->absolutePath($provider, $path);
    }

    public function delete(string $provider, array $paths): bool
    {
        return $this->delegate->delete($provider, $paths);
    }
}

/**
 * Simula outra requisição trocando o original enquanto as versões são geradas.
 */
class ConcurrentReplacementVersions extends OptimizedImageVersions
{
    public function render(ItemAcervo $item, Arquivo $original, array $versions, StorageMutationJournal $journal): array
    {
        $resultado = parent::render($item, $original, $versions, $journal);
        Arquivo::query()->whereKey($original->id)->update(['sha256' => str_repeat('f', 64)]);

        return $resultado;
    }
}
