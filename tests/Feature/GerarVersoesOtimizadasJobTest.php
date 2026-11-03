<?php

namespace Tests\Feature;

use App\Auditing\AuditContext;
use App\Auditing\Enums\AuditAction;
use App\Auditing\Enums\AuditSource;
use App\Enums\StatusDerivacoes;
use App\Enums\Visibilidade;
use App\Jobs\GerarVersoesOtimizadas;
use App\Models\Arquivo;
use App\Models\AuditEvent;
use App\Models\ItemAcervo;
use App\Models\User;
use App\Queries\Publico\ConsultaFotografiasPublicas;
use App\Services\Acervo\CriarItemAcervo;
use App\Services\Arquivos\Contracts\ArquivoStorage;
use App\Services\Arquivos\LaravelArquivoStorage;
use App\Services\Arquivos\StorageMutationJournal;
use App\Support\OptimizedImageVersions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use JsonException;
use RuntimeException;
use Tests\TestCase;

class GerarVersoesOtimizadasJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        TemporaryFailureStorage::$falhasRestantes = 0;
    }

    public function test_sync_mode_generates_in_the_request_without_dispatching(): void
    {
        Queue::fake();
        config(['acervo.optimized_processing.async' => false]);

        $fotografia = $this->photographWithOriginal($this->internalUser());

        Queue::assertNothingPushed();
        $this->assertSame(StatusDerivacoes::Concluido, $this->original($fotografia)->derivacoes_status);
        $this->assertSame(['large', 'medium', 'thumbnail'], $this->versions($fotografia));
        $this->assertDerivativeFilesMatchRecords($fotografia);
    }

    public function test_async_upload_keeps_the_original_pending_and_dispatches_the_job(): void
    {
        Queue::fake();
        $this->enableAsync();
        $admin = $this->internalUser();

        $this->actingAs($admin)
            ->post(route('admin.fotografias.store'), $this->itemAttributes([
                'arquivo_original' => UploadedFile::fake()->image('original.jpg', 2400, 1600),
            ]))
            ->assertSessionHas('success', 'Fotografia cadastrada com sucesso. As versões otimizadas serão geradas em segundo plano.');

        $fotografia = ItemAcervo::query()->sole();
        $original = $this->original($fotografia);
        $upload = AuditEvent::query()->where('action', AuditAction::Uploaded)->sole();

        $this->assertSame(StatusDerivacoes::Pendente, $original->derivacoes_status);
        $this->assertSame([], $this->versions($fotografia));
        Storage::disk('local')->assertExists($original->storage_path);
        $this->assertSame('pendente', $upload->metadata['derivations']['status']);
        $this->assertSame([], $upload->metadata['derivations']['generated']);
        Queue::assertPushedOn('imagens', GerarVersoesOtimizadas::class, fn (GerarVersoesOtimizadas $job): bool => $job->arquivoId === $original->id
            && $job->sha256 === $original->sha256
            && $job->correlationId === $upload->correlation_id
            && $job->afterCommit === true);
        Queue::assertPushed(GerarVersoesOtimizadas::class, 1);
    }

    public function test_async_edit_with_upload_and_replacement_dispatch_the_job(): void
    {
        Queue::fake();
        $this->enableAsync();
        $admin = $this->internalUser();
        $this->actingAs($admin)->post(route('admin.fotografias.store'), $this->itemAttributes())->assertSessionHasNoErrors();
        $fotografia = ItemAcervo::query()->sole();
        Queue::assertNothingPushed();

        $this->actingAs($admin)
            ->put(route('admin.fotografias.update', $fotografia), $this->itemAttributes([
                'arquivo_original' => UploadedFile::fake()->image('original.jpg', 2400, 1600),
            ]))
            ->assertSessionHasNoErrors();
        $primeiro = $this->original($fotografia);

        $this->actingAs($admin)
            ->put(route('admin.fotografias.replace-original', $fotografia), [
                'arquivo_original' => UploadedFile::fake()->image('nova.jpg', 1600, 2400),
            ])
            ->assertSessionHasNoErrors();
        $substituto = $this->original($fotografia);

        $this->assertSame($primeiro->id, $substituto->id);
        $this->assertNotSame($primeiro->sha256, $substituto->sha256);
        $this->assertSame(StatusDerivacoes::Pendente, $substituto->derivacoes_status);
        Queue::assertPushed(GerarVersoesOtimizadas::class, 2);
        Queue::assertPushed(GerarVersoesOtimizadas::class, fn (GerarVersoesOtimizadas $job): bool => $job->sha256 === $primeiro->sha256);
        Queue::assertPushed(GerarVersoesOtimizadas::class, fn (GerarVersoesOtimizadas $job): bool => $job->sha256 === $substituto->sha256);
    }

    public function test_job_is_queued_only_when_the_outer_transaction_commits(): void
    {
        $this->enableAsync();
        $admin = $this->internalUser();

        DB::transaction(function () use ($admin): void {
            $this->createWithService($admin);

            $this->assertSame(0, DB::table('jobs')->count());
        });

        $this->assertSame(1, DB::table('jobs')->count());
    }

    public function test_rolled_back_creation_queues_nothing_and_leaves_no_file(): void
    {
        $this->enableAsync();
        $admin = $this->internalUser();

        try {
            // Título com UTF-8 inválido faz a gravação da auditoria falhar dentro da transação.
            $this->createWithService($admin, ['titulo' => "Inválida \xB1"]);
            $this->fail('A gravação da auditoria deveria rejeitar JSON inválido.');
        } catch (JsonException) {
            // A transação foi revertida.
        }

        $this->assertSame(0, DB::table('jobs')->count());
        $this->assertSame(0, Arquivo::query()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());

        try {
            DB::transaction(function () use ($admin): void {
                $this->createWithService($admin);

                throw new RuntimeException('Falha posterior na transação externa.');
            });
        } catch (RuntimeException) {
            // O despacho guardado para o commit é descartado.
        }

        $this->assertSame(0, DB::table('jobs')->count());
    }

    public function test_worker_generates_the_versions_and_audits_as_a_job(): void
    {
        $this->enableAsync();
        $admin = $this->internalUser();
        $fotografia = $this->photographWithOriginal($admin);
        $this->publish($fotografia);
        $consulta = app(ConsultaFotografiasPublicas::class);

        $this->assertNull($consulta->porIdentificador($fotografia->id));
        $this->assertSame(1, DB::table('jobs')->where('queue', 'imagens')->count());

        $this->runWorker();

        $original = $this->original($fotografia);
        $this->assertSame(StatusDerivacoes::Concluido, $original->derivacoes_status);
        $this->assertSame(['large', 'medium', 'thumbnail'], $this->versions($fotografia));
        $this->assertDerivativeFilesMatchRecords($fotografia);
        $this->assertNotNull($consulta->porIdentificador($fotografia->id));
        $this->assertSame(0, DB::table('jobs')->count());
        $this->assertSame(0, DB::table('failed_jobs')->count());

        $upload = AuditEvent::query()->where('action', AuditAction::Uploaded)->sole();
        $geracao = AuditEvent::query()->where('action', AuditAction::Regenerated)->sole();
        $this->assertSame(AuditSource::Web, $upload->source);
        $this->assertSame(AuditSource::Job, $geracao->source);
        $this->assertNull($geracao->actor_user_id);
        $this->assertSame($upload->correlation_id, $geracao->correlation_id);
        $this->assertSame((string) $original->id, $geracao->subject_id);
        $this->assertSame('ausentes', $geracao->metadata['mode']);
        $this->assertCount(3, $geracao->metadata['derivations']['generated']);
    }

    public function test_repeated_jobs_do_not_duplicate_versions_or_events(): void
    {
        $this->enableAsync();
        $fotografia = $this->photographWithOriginal($this->internalUser());
        $original = $this->original($fotografia);
        $repetido = fn () => Bus::dispatch(new GerarVersoesOtimizadas($original->id, $original->sha256, (string) Str::uuid()));
        $repetido();

        $this->runWorker();
        $depoisDoPrimeiro = $this->derivatives($fotografia)->pluck('id')->sort()->values()->all();
        $repetido();
        $this->runWorker();

        $this->assertSame($depoisDoPrimeiro, $this->derivatives($fotografia)->pluck('id')->sort()->values()->all());
        $this->assertSame(1, AuditEvent::query()->where('action', AuditAction::Regenerated)->count());
        $this->assertSame(StatusDerivacoes::Concluido, $this->original($fotografia)->derivacoes_status);
        $this->assertDerivativeFilesMatchRecords($fotografia);
        $this->assertSame(0, DB::table('failed_jobs')->count());
    }

    public function test_replacement_before_the_job_runs_discards_the_old_job(): void
    {
        $this->enableAsync();
        $admin = $this->internalUser();
        $fotografia = $this->photographWithOriginal($admin);
        $this->actingAs($admin)
            ->put(route('admin.fotografias.replace-original', $fotografia), [
                'arquivo_original' => UploadedFile::fake()->image('retrato.jpg', 1600, 2400),
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, DB::table('jobs')->count());

        $this->runWorker();

        $large = $this->derivatives($fotografia)['large'];
        // As versões são do original novo (retrato), não do antigo (paisagem).
        $this->assertGreaterThan($large->width, $large->height);
        $this->assertSame(StatusDerivacoes::Concluido, $this->original($fotografia)->derivacoes_status);
        $this->assertSame(1, AuditEvent::query()->where('action', AuditAction::Regenerated)->count());
        $this->assertDerivativeFilesMatchRecords($fotografia);
        $this->assertSame(0, DB::table('failed_jobs')->count());
    }

    public function test_replacement_while_the_job_renders_associates_nothing_from_the_old_original(): void
    {
        $this->enableAsync();
        $fotografia = $this->photographWithOriginal($this->internalUser());
        $this->app->bind(OptimizedImageVersions::class, ReplacedWhileRenderingVersions::class);

        $this->runWorker();

        $original = $this->original($fotografia);
        $this->assertSame(str_repeat('d', 64), $original->sha256);
        $this->assertSame(StatusDerivacoes::Pendente, $original->derivacoes_status);
        $this->assertSame([], $this->versions($fotografia));
        $this->assertSame([], Storage::disk('local')->files("acervo/derivados/{$fotografia->id}"));
        $this->assertSame(0, AuditEvent::query()->where('action', AuditAction::Regenerated)->count());
        $this->assertSame(0, DB::table('jobs')->count());
        $this->assertSame(0, DB::table('failed_jobs')->count());
    }

    public function test_force_deleted_photograph_with_a_pending_job_is_ignored(): void
    {
        $this->enableAsync();
        $admin = $this->internalUser();
        $fotografia = $this->photographWithOriginal($admin);
        $this->actingAs($admin)->delete(route('admin.fotografias.destroy', $fotografia));
        $this->actingAs($admin)->delete(route('admin.fotografias.force-destroy', $fotografia->id))->assertSessionHasNoErrors();

        $this->runWorker();

        $this->assertSame(0, Arquivo::query()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertSame(0, DB::table('jobs')->count());
        $this->assertSame(0, DB::table('failed_jobs')->count());
    }

    public function test_soft_deleted_photograph_is_processed_and_returns_complete_when_restored(): void
    {
        $this->enableAsync();
        $admin = $this->internalUser();
        $fotografia = $this->photographWithOriginal($admin);
        $this->publish($fotografia);
        $this->actingAs($admin)->delete(route('admin.fotografias.destroy', $fotografia));

        $this->runWorker();

        $this->assertSame(['large', 'medium', 'thumbnail'], $this->versions($fotografia));
        $this->assertSame(StatusDerivacoes::Concluido, $this->original($fotografia)->derivacoes_status);
        $this->assertNull(app(ConsultaFotografiasPublicas::class)->porIdentificador($fotografia->id));

        $this->actingAs($admin)->patch(route('admin.fotografias.restore', $fotografia->id))->assertSessionHasNoErrors();

        $this->assertNotNull(app(ConsultaFotografiasPublicas::class)->porIdentificador($fotografia->id));
        $this->assertDerivativeFilesMatchRecords($fotografia);
    }

    public function test_partial_failure_is_retried_and_then_completes(): void
    {
        $this->enableAsync();
        $this->failVersionTemporarily('medium', times: 1);
        $fotografia = $this->photographWithOriginal($this->internalUser());

        $this->runWorker();

        $this->assertSame(StatusDerivacoes::Pendente, $this->original($fotografia)->derivacoes_status);
        $this->assertSame(['large', 'thumbnail'], $this->versions($fotografia));
        $this->assertSame(1, DB::table('jobs')->count());

        $this->travel(31)->seconds();
        $this->runWorker();

        $this->assertSame(StatusDerivacoes::Concluido, $this->original($fotografia)->derivacoes_status);
        $this->assertSame(['large', 'medium', 'thumbnail'], $this->versions($fotografia));
        $this->assertDerivativeFilesMatchRecords($fotografia);
        $this->assertSame(0, DB::table('jobs')->count());
        $this->assertSame(0, DB::table('failed_jobs')->count());
    }

    public function test_partial_failure_on_every_attempt_ends_as_failed(): void
    {
        $this->enableAsync();
        $this->failVersionTemporarily('medium', times: 10);
        $fotografia = $this->photographWithOriginal($this->internalUser());

        $this->runAllAttempts();

        $this->assertSame(StatusDerivacoes::Falhou, $this->original($fotografia)->derivacoes_status);
        $this->assertSame(['large', 'thumbnail'], $this->versions($fotografia));
        $this->assertDerivativeFilesMatchRecords($fotografia);
        $this->assertSame(0, DB::table('jobs')->count());
        $this->assertSame(1, DB::table('failed_jobs')->count());
    }

    public function test_exception_on_every_attempt_ends_as_failed(): void
    {
        $this->enableAsync();
        $fotografia = $this->photographWithOriginal($this->internalUser());
        // Título com UTF-8 inválido faz a auditoria do job falhar em toda tentativa.
        DB::table('item_acervos')->where('id', $fotografia->id)->update(['titulo' => "Inválida \xB1"]);

        $this->runWorker();
        $this->assertSame(StatusDerivacoes::Pendente, $this->original($fotografia)->derivacoes_status);

        $this->runAllAttempts();

        $this->assertSame(StatusDerivacoes::Falhou, $this->original($fotografia)->derivacoes_status);
        $this->assertSame([], $this->versions($fotografia));
        $this->assertSame([], Storage::disk('local')->files("acervo/derivados/{$fotografia->id}"));
        $this->assertSame(1, DB::table('failed_jobs')->count());
    }

    public function test_a_stale_processing_reservation_is_taken_over_but_a_recent_one_is_not(): void
    {
        $this->enableAsync();
        $fotografia = $this->photographWithOriginal($this->internalUser());
        $original = $this->original($fotografia);
        Arquivo::query()->whereKey($original->id)->update([
            'derivacoes_status' => StatusDerivacoes::Processando->value,
            'derivacoes_atualizadas_em' => now()->subSeconds(60),
        ]);

        $this->runWorker();

        // Outro processamento recente: o job encerra sem tocar em nada.
        $this->assertSame(StatusDerivacoes::Processando, $this->original($fotografia)->derivacoes_status);
        $this->assertSame([], $this->versions($fotografia));

        Arquivo::query()->whereKey($original->id)->update(['derivacoes_atualizadas_em' => now()->subSeconds(301)]);
        Bus::dispatch(new GerarVersoesOtimizadas($original->id, $original->sha256, (string) Str::uuid()));
        $this->runWorker();

        $this->assertSame(StatusDerivacoes::Concluido, $this->original($fotografia)->derivacoes_status);
        $this->assertDerivativeFilesMatchRecords($fotografia);
    }

    public function test_pdf_is_not_queued_in_async_mode(): void
    {
        Queue::fake();
        $this->enableAsync();

        $fotografia = $this->photographWithOriginal(
            $this->internalUser(),
            UploadedFile::fake()->createWithContent('documento.pdf', "%PDF-1.4\n".Str::random(40)."\n%%EOF"),
        );

        Queue::assertNothingPushed();
        $this->assertSame(StatusDerivacoes::NaoAplicavel, $this->original($fotografia)->derivacoes_status);
    }

    public function test_missing_mode_command_recovers_a_pending_photograph_whose_dispatch_was_lost(): void
    {
        $this->enableAsync();
        $fotografia = $this->photographWithOriginal($this->internalUser());
        DB::table('jobs')->delete();

        $this->artisan('acervo:regenerar-versoes', ['--ausentes' => true])->assertSuccessful();

        $this->assertSame(StatusDerivacoes::Concluido, $this->original($fotografia)->derivacoes_status);
        $this->assertDerivativeFilesMatchRecords($fotografia);
        $this->assertSame(AuditSource::Console, AuditEvent::query()->where('action', AuditAction::Regenerated)->sole()->source);
    }

    private function enableAsync(): void
    {
        config([
            'acervo.optimized_processing.async' => true,
            'queue.default' => 'database',
        ]);
    }

    private function runWorker(): void
    {
        Artisan::call('queue:work', [
            'connection' => 'database',
            '--queue' => 'imagens',
            '--stop-when-empty' => true,
            '--sleep' => 0,
        ]);
    }

    /**
     * Avança o relógio além de cada backoff do job, até as tentativas acabarem.
     */
    private function runAllAttempts(): void
    {
        $this->runWorker();

        foreach ([31, 121] as $segundos) {
            $this->travel($segundos)->seconds();
            $this->runWorker();
        }
    }

    private function failVersionTemporarily(string $version, int $times): void
    {
        TemporaryFailureStorage::$falhasRestantes = $times;
        $this->app->bind(
            ArquivoStorage::class,
            fn (): ArquivoStorage => new TemporaryFailureStorage(new LaravelArquivoStorage, $version),
        );
    }

    private function createWithService(User $admin, array $override = []): ItemAcervo
    {
        return app(CriarItemAcervo::class)->execute(
            $admin,
            $this->itemAttributes($override),
            AuditContext::forUser($admin, AuditSource::Web, (string) Str::uuid()),
            UploadedFile::fake()->image('original.jpg', 2400, 1600),
        );
    }

    private function publish(ItemAcervo $fotografia): void
    {
        ItemAcervo::query()->withTrashed()->whereKey($fotografia->id)->update(['status' => 'publicado', 'visibilidade' => 'publico']);
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
        return Arquivo::query()->where('item_acervo_id', $fotografia->id)->where('versao_arquivo', 'original')->sole();
    }

    private function derivatives(ItemAcervo $fotografia)
    {
        return Arquivo::query()
            ->where('item_acervo_id', $fotografia->id)
            ->where('versao_arquivo', '!=', 'original')
            ->orderBy('versao_arquivo')
            ->get()
            ->keyBy('versao_arquivo');
    }

    /** @return list<string> */
    private function versions(ItemAcervo $fotografia): array
    {
        return $this->derivatives($fotografia)->keys()->all();
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

    private function internalUser(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'ativo']);
    }
}

/**
 * Falha ao gravar a versão informada nas primeiras N vezes, como um disco cheio que se recupera.
 */
final class TemporaryFailureStorage implements ArquivoStorage
{
    public static int $falhasRestantes = 0;

    public function __construct(
        private readonly ArquivoStorage $delegate,
        private readonly string $version,
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
        if (str_contains($path, "-{$this->version}.") && self::$falhasRestantes > 0) {
            self::$falhasRestantes--;

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
 * Simula outra requisição substituindo o original enquanto o job gera as versões.
 */
class ReplacedWhileRenderingVersions extends OptimizedImageVersions
{
    public function render(ItemAcervo $item, Arquivo $original, array $versions, StorageMutationJournal $journal): array
    {
        $resultado = parent::render($item, $original, $versions, $journal);
        Arquivo::query()->whereKey($original->id)->update([
            'sha256' => str_repeat('d', 64),
            'derivacoes_status' => StatusDerivacoes::Pendente->value,
        ]);

        return $resultado;
    }
}
