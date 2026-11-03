<?php

namespace Tests\Feature;

use App\Auditing\AuditContext;
use App\Auditing\Enums\AuditAction;
use App\Auditing\Enums\AuditEntity;
use App\Auditing\Enums\AuditSource;
use App\Enums\StatusDerivacoes;
use App\Enums\Visibilidade;
use App\Models\Arquivo;
use App\Models\AuditEvent;
use App\Models\ItemAcervo;
use App\Models\User;
use App\Queries\Publico\ConsultaFotografiasPublicas;
use App\Services\Arquivos\Contracts\ArquivoStorage;
use App\Services\Arquivos\LaravelArquivoStorage;
use App\Services\Arquivos\RegenerarVersoesOtimizadas;
use App\Services\Arquivos\StorageMutationJournal;
use App\Support\OptimizedImageVersions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use JsonException;
use RuntimeException;
use Tests\TestCase;

class StatusDerivacoesTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_upload_is_completed_with_every_expected_version(): void
    {
        Storage::fake('local');
        $fotografia = $this->photographWithOriginal($this->internalUser());
        $original = $this->original($fotografia);

        $this->assertSame(StatusDerivacoes::Concluido, $original->derivacoes_status);
        $this->assertNotNull($original->derivacoes_atualizadas_em);
        $this->assertSame(['large', 'medium', 'thumbnail'], $this->versions($fotografia));
        $this->assertStatusMatchesDerivatives($fotografia);
        // Os derivados não carregam estado próprio.
        $this->assertSame([null], $fotografia->arquivos()->where('versao_arquivo', '!=', 'original')->pluck('derivacoes_status')->unique()->all());
    }

    public function test_dispensed_versions_do_not_count_as_failure(): void
    {
        Storage::fake('local');
        $fotografia = $this->photographWithOriginal($this->internalUser(), UploadedFile::fake()->image('pequena.jpg', 300, 200));

        $this->assertSame(['thumbnail'], $this->versions($fotografia));
        $this->assertSame(StatusDerivacoes::Concluido, $this->original($fotografia)->derivacoes_status);
        $this->assertStatusMatchesDerivatives($fotografia);
    }

    public function test_partial_failure_on_upload_is_failed_and_keeps_the_generated_versions(): void
    {
        Storage::fake('local');
        $this->failVersion('medium');
        $fotografia = $this->photographWithOriginal($this->internalUser());

        $this->assertSame(StatusDerivacoes::Falhou, $this->original($fotografia)->derivacoes_status);
        $this->assertSame(['large', 'thumbnail'], $this->versions($fotografia));
        $this->assertStatusMatchesDerivatives($fotografia);
    }

    public function test_total_failure_on_upload_is_failed_without_derivatives(): void
    {
        Storage::fake('local');
        $this->failVersion(null);
        $fotografia = $this->photographWithOriginal($this->internalUser());

        $this->assertSame(StatusDerivacoes::Falhou, $this->original($fotografia)->derivacoes_status);
        $this->assertSame([], $this->versions($fotografia));
    }

    public function test_pdf_original_is_not_applicable_and_never_pending(): void
    {
        Storage::fake('local');
        $fotografia = $this->photographWithOriginal($this->internalUser(), $this->pdf());
        $original = $this->original($fotografia);

        $this->assertSame(StatusDerivacoes::NaoAplicavel, $original->derivacoes_status);
        $this->assertNotNull($original->derivacoes_atualizadas_em);
        $this->assertSame([], $this->versions($fotografia));
    }

    public function test_replacement_restarts_the_status_from_the_new_original(): void
    {
        Storage::fake('local');
        $admin = $this->internalUser();
        $this->failVersion(null);
        $fotografia = $this->photographWithOriginal($admin);
        $this->assertSame(StatusDerivacoes::Falhou, $this->original($fotografia)->derivacoes_status);
        $this->app->forgetInstance(ArquivoStorage::class);
        $this->app->bind(ArquivoStorage::class, LaravelArquivoStorage::class);

        $this->replace($admin, $fotografia, UploadedFile::fake()->image('nova.jpg', 2000, 1400));
        $this->assertSame(StatusDerivacoes::Concluido, $this->original($fotografia)->derivacoes_status);
        $this->assertStatusMatchesDerivatives($fotografia);

        $this->replace($admin, $fotografia, $this->pdf());
        $this->assertSame(StatusDerivacoes::NaoAplicavel, $this->original($fotografia)->derivacoes_status);
        $this->assertSame([], $this->versions($fotografia));

        $this->replace($admin, $fotografia, UploadedFile::fake()->image('de-volta.jpg', 1800, 1200));
        $this->assertSame(StatusDerivacoes::Concluido, $this->original($fotografia)->derivacoes_status);
        $this->assertStatusMatchesDerivatives($fotografia);
    }

    public function test_regeneration_completes_a_failed_photograph(): void
    {
        Storage::fake('local');
        $admin = $this->internalUser();
        $this->failVersion('large');
        $fotografia = $this->photographWithOriginal($admin);
        $this->assertSame(StatusDerivacoes::Falhou, $this->original($fotografia)->derivacoes_status);
        $this->app->bind(ArquivoStorage::class, LaravelArquivoStorage::class);

        $this->actingAs($admin)->post(route('admin.fotografias.regenerate-versions', $fotografia));

        $this->assertSame(StatusDerivacoes::Concluido, $this->original($fotografia)->derivacoes_status);
        $this->assertStatusMatchesDerivatives($fotografia);
    }

    public function test_partial_failure_on_regeneration_is_failed(): void
    {
        Storage::fake('local');
        $admin = $this->internalUser();
        $fotografia = $this->photographWithOriginal($admin);
        $this->failVersion('thumbnail');

        $this->actingAs($admin)->post(route('admin.fotografias.regenerate-versions', $fotografia));

        // A miniatura anterior foi mantida, mas a geração pedida não se completou.
        $this->assertSame(['large', 'medium', 'thumbnail'], $this->versions($fotografia));
        $this->assertSame(StatusDerivacoes::Falhou, $this->original($fotografia)->derivacoes_status);
    }

    public function test_regeneration_marks_processing_while_rendering(): void
    {
        Storage::fake('local');
        $admin = $this->internalUser();
        $fotografia = $this->photographWithOriginal($admin);
        $this->app->bind(OptimizedImageVersions::class, StatusObservingVersions::class);

        app(RegenerarVersoesOtimizadas::class)->execute($admin, $fotografia, $this->context($admin));

        $this->assertSame([StatusDerivacoes::Processando], StatusObservingVersions::$observados);
        $this->assertSame(StatusDerivacoes::Concluido, $this->original($fotografia)->derivacoes_status);
    }

    public function test_audit_failure_during_regeneration_restores_the_previous_status(): void
    {
        Storage::fake('local');
        $admin = $this->internalUser();
        $this->failVersion('medium');
        $fotografia = $this->photographWithOriginal($admin);
        $this->app->bind(ArquivoStorage::class, LaravelArquivoStorage::class);
        // Título com UTF-8 inválido faz a gravação da auditoria falhar dentro da transação.
        DB::table('item_acervos')->where('id', $fotografia->id)->update(['titulo' => "Inválida \xB1"]);

        try {
            app(RegenerarVersoesOtimizadas::class)->execute($admin, $fotografia->fresh(), $this->context($admin));
            $this->fail('A gravação da auditoria deveria rejeitar JSON inválido.');
        } catch (JsonException) {
            // As versões anteriores foram mantidas.
        }

        $this->assertSame(StatusDerivacoes::Falhou, $this->original($fotografia)->derivacoes_status);
        $this->assertSame(['large', 'thumbnail'], $this->versions($fotografia));
    }

    public function test_original_replaced_during_regeneration_keeps_the_replacement_status(): void
    {
        Storage::fake('local');
        $admin = $this->internalUser();
        $fotografia = $this->photographWithOriginal($admin);
        $this->app->bind(OptimizedImageVersions::class, ReplacingDuringRenderVersions::class);

        try {
            app(RegenerarVersoesOtimizadas::class)->execute($admin, $fotografia, $this->context($admin));
            $this->fail('A regeneração deveria detectar a troca do original.');
        } catch (RuntimeException $exception) {
            $this->assertSame('O arquivo original mudou durante a regeneração das versões.', $exception->getMessage());
        }

        $this->assertSame(StatusDerivacoes::NaoAplicavel, $this->original($fotografia)->derivacoes_status);
    }

    public function test_regeneration_of_a_pdf_records_not_applicable(): void
    {
        Storage::fake('local');
        $admin = $this->internalUser();
        $fotografia = $this->photographWithOriginal($admin, $this->pdf());
        Arquivo::query()->whereKey($this->original($fotografia)->id)->update(['derivacoes_status' => null]);

        $this->actingAs($admin)->post(route('admin.fotografias.regenerate-versions', $fotografia));

        $this->assertSame(StatusDerivacoes::NaoAplicavel, $this->original($fotografia)->derivacoes_status);
        $this->assertSame(0, AuditEvent::query()->where('action', AuditAction::Regenerated)->count());
    }

    public function test_migration_only_classifies_existing_non_image_originals(): void
    {
        $migration = require database_path('migrations/2026_11_03_000002_add_derivacoes_status_to_arquivos_table.php');
        $migration->down();
        $linha = fn (ItemAcervo $item, string $versao, string $mime): array => [
            'item_acervo_id' => $item->id,
            'nome_original' => $versao === 'original' ? 'arquivo' : null,
            'provider' => 'local',
            'storage_path' => 'acervo/'.Str::uuid(),
            'mime_type' => $mime,
            'file_size' => 10,
            'tipo_arquivo' => str_starts_with($mime, 'image/') ? 'imagem' : 'documento',
            'sha256' => hash('sha256', (string) Str::uuid()),
            'versao_arquivo' => $versao,
        ];
        $fotografia = ItemAcervo::create($this->itemAttributes());
        $documento = ItemAcervo::create($this->itemAttributes(['titulo' => 'Ata']));
        $imagem = DB::table('arquivos')->insertGetId($linha($fotografia, 'original', 'image/jpeg'));
        $derivado = DB::table('arquivos')->insertGetId($linha($fotografia, 'thumbnail', 'image/jpeg'));
        $pdf = DB::table('arquivos')->insertGetId($linha($documento, 'original', 'application/pdf'));

        $migration->up();

        $estados = DB::table('arquivos')->pluck('derivacoes_status', 'id');
        $this->assertNull($estados[$imagem]);
        $this->assertNull($estados[$derivado]);
        $this->assertSame('nao_aplicavel', $estados[$pdf]);
        $this->assertSame([null], DB::table('arquivos')->pluck('derivacoes_atualizadas_em')->unique()->all());
    }

    public function test_missing_mode_command_classifies_a_legacy_complete_photograph_without_regenerating(): void
    {
        Storage::fake('local');
        $fotografia = $this->photographWithOriginal($this->internalUser());
        $original = $this->original($fotografia);
        Arquivo::query()->whereKey($original->id)->update(['derivacoes_status' => null, 'derivacoes_atualizadas_em' => null]);
        $antigas = $fotografia->arquivos()->where('versao_arquivo', '!=', 'original')->pluck('id')->sort()->values()->all();

        $this->artisan('acervo:regenerar-versoes', ['--ausentes' => true])
            ->expectsOutputToContain('nada a gerar')
            ->assertSuccessful();

        $this->assertSame(StatusDerivacoes::Concluido, $this->original($fotografia)->derivacoes_status);
        $this->assertSame($antigas, $fotografia->arquivos()->where('versao_arquivo', '!=', 'original')->pluck('id')->sort()->values()->all());
        $this->assertSame(0, AuditEvent::query()->where('action', AuditAction::Regenerated)->count());
    }

    public function test_status_stays_out_of_the_audit_snapshots_but_is_summarized_in_the_metadata(): void
    {
        Storage::fake('local');
        $admin = $this->internalUser();
        $fotografia = $this->photographWithOriginal($admin);
        $this->replace($admin, $fotografia, UploadedFile::fake()->image('nova.jpg', 1600, 1200));

        $eventos = AuditEvent::query()->where('subject_type', AuditEntity::Arquivo)->get();

        $this->assertSame([AuditAction::Uploaded, AuditAction::Replaced], $eventos->pluck('action')->all());

        foreach ($eventos as $evento) {
            $this->assertArrayNotHasKey('derivacoes_status', $evento->after ?? []);
            $this->assertArrayNotHasKey('derivacoes_status', $evento->before ?? []);
            $this->assertSame('concluido', $evento->metadata['derivations']['status']);
        }
    }

    public function test_publication_still_depends_only_on_existing_versions(): void
    {
        Storage::fake('local');
        $admin = $this->internalUser();
        $this->failVersion('medium');
        $parcial = $this->photographWithOriginal($admin);
        $this->failVersion(null);
        $semVersoes = $this->photographWithOriginal($admin, UploadedFile::fake()->image('outra.jpg', 2000, 1500));
        ItemAcervo::query()->whereKey([$parcial->id, $semVersoes->id])->update(['status' => 'publicado', 'visibilidade' => 'publico']);
        $consulta = app(ConsultaFotografiasPublicas::class);

        $this->assertSame(StatusDerivacoes::Falhou, $this->original($parcial)->derivacoes_status);
        $this->assertNotNull($consulta->porIdentificador($parcial->id));
        $this->assertSame(StatusDerivacoes::Falhou, $this->original($semVersoes)->derivacoes_status);
        $this->assertNull($consulta->porIdentificador($semVersoes->id));
    }

    /**
     * Concluído significa que toda versão esperada tem registro e arquivo.
     */
    private function assertStatusMatchesDerivatives(ItemAcervo $fotografia): void
    {
        $original = $this->original($fotografia);
        $esperadas = app(OptimizedImageVersions::class)->expectedVersions($original);
        $completo = array_diff($esperadas, $this->versions($fotografia)) === [];

        $this->assertSame($completo, $original->derivacoes_status === StatusDerivacoes::Concluido);

        foreach ($fotografia->arquivos()->where('versao_arquivo', '!=', 'original')->get() as $derivado) {
            Storage::disk('local')->assertExists($derivado->storage_path);
        }
    }

    private function failVersion(?string $version): void
    {
        $this->app->bind(
            ArquivoStorage::class,
            fn (): ArquivoStorage => new StatusFailingVersionStorage(new LaravelArquivoStorage, $version),
        );
    }

    private function photographWithOriginal(User $actor, ?UploadedFile $arquivo = null): ItemAcervo
    {
        $this->actingAs($actor)
            ->post(route('admin.fotografias.store'), $this->itemAttributes([
                'arquivo_original' => $arquivo ?? UploadedFile::fake()->image('original.jpg', 2400, 1600),
            ]))
            ->assertSessionHasNoErrors();

        return ItemAcervo::query()->latest('id')->firstOrFail();
    }

    private function replace(User $actor, ItemAcervo $fotografia, UploadedFile $arquivo): void
    {
        $this->actingAs($actor)
            ->put(route('admin.fotografias.replace-original', $fotografia), ['arquivo_original' => $arquivo])
            ->assertSessionHasNoErrors();
    }

    private function pdf(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('documento.pdf', "%PDF-1.4\n".Str::random(40)."\n%%EOF");
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
            'visibilidade' => Visibilidade::Privado->value,
        ];
    }

    private function original(ItemAcervo $fotografia): Arquivo
    {
        return $fotografia->arquivos()->where('versao_arquivo', 'original')->sole();
    }

    /** @return list<string> */
    private function versions(ItemAcervo $fotografia): array
    {
        return $fotografia->arquivos()
            ->where('versao_arquivo', '!=', 'original')
            ->orderBy('versao_arquivo')
            ->pluck('versao_arquivo')
            ->all();
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
final readonly class StatusFailingVersionStorage implements ArquivoStorage
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
 * Anota o estado do original gravado no banco no momento em que as versões são geradas.
 */
class StatusObservingVersions extends OptimizedImageVersions
{
    /** @var list<StatusDerivacoes|null> */
    public static array $observados = [];

    public function render(ItemAcervo $item, Arquivo $original, array $versions, StorageMutationJournal $journal): array
    {
        static::$observados[] = Arquivo::query()->findOrFail($original->id)->derivacoes_status;

        return parent::render($item, $original, $versions, $journal);
    }
}

/**
 * Simula outra requisição substituindo o original por um PDF enquanto as versões são geradas.
 */
class ReplacingDuringRenderVersions extends OptimizedImageVersions
{
    public function render(ItemAcervo $item, Arquivo $original, array $versions, StorageMutationJournal $journal): array
    {
        $resultado = parent::render($item, $original, $versions, $journal);
        Arquivo::query()->whereKey($original->id)->update([
            'sha256' => str_repeat('e', 64),
            'mime_type' => 'application/pdf',
            'derivacoes_status' => StatusDerivacoes::NaoAplicavel->value,
        ]);

        return $resultado;
    }
}
