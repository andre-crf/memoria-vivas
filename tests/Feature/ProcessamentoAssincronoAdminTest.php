<?php

namespace Tests\Feature;

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
use App\Services\Arquivos\Contracts\ArquivoStorage;
use App\Services\Arquivos\LaravelArquivoStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use RuntimeException;
use Tests\TestCase;

class ProcessamentoAssincronoAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        AsyncAdminFailingStorage::$falhasRestantes = 0;
    }

    public function test_upload_feedback_matches_each_mode(): void
    {
        $admin = $this->internalUser();

        $this->upload($admin)->assertSessionHas('success', 'Fotografia cadastrada com sucesso.');

        $this->enableAsync();
        $this->upload($admin, UploadedFile::fake()->image('outra.jpg', 2000, 1500))
            ->assertSessionHas('success', 'Fotografia cadastrada com sucesso. As versões otimizadas serão geradas em segundo plano.');
        $fotografia = ItemAcervo::query()->latest('id')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.fotografias.replace-original', $fotografia), ['arquivo_original' => UploadedFile::fake()->image('nova.jpg', 1800, 1200)])
            ->assertSessionHas('success', 'Arquivo original substituído com sucesso. As versões otimizadas serão geradas em segundo plano.');

        $this->actingAs($admin)->post(route('admin.fotografias.store'), $this->itemAttributes(['titulo' => 'Sem arquivo']));
        $semArquivo = ItemAcervo::query()->latest('id')->firstOrFail();
        $this->actingAs($admin)
            ->put(route('admin.fotografias.update', $semArquivo), $this->itemAttributes([
                'titulo' => 'Sem arquivo',
                'arquivo_original' => UploadedFile::fake()->image('editada.jpg', 1700, 1100),
            ]))
            ->assertSessionHas('success', 'Fotografia atualizada com sucesso. As versões otimizadas serão geradas em segundo plano.');

        // O PDF continua com a mensagem própria, sem "segundo plano".
        $this->upload($admin, UploadedFile::fake()->createWithContent('documento.pdf', "%PDF-1.4\n".Str::random(40)."\n%%EOF"))
            ->assertSessionHas('warning', 'Arquivo original salvo. PDFs são preservados, mas não geram versões de imagem e não podem ser exibidos no catálogo público.');
    }

    public function test_sync_mode_keeps_reporting_the_immediate_failure(): void
    {
        $this->failVersion('medium', times: 10);

        $this->upload($this->internalUser())
            ->assertSessionHas('warning', 'O arquivo original foi preservado, mas algumas versões não foram geradas (Média). As versões disponíveis continuam acessíveis; tente regenerar as versões ausentes.');
    }

    public function test_show_page_presents_pending_without_failure_messages(): void
    {
        $this->enableAsync();
        $admin = $this->internalUser();
        $fotografia = $this->photographWithOriginal($admin);

        $this->show($admin, $fotografia)
            ->assertSee('data-status-derivacoes="pendente"', false)
            ->assertSee('Pendente')
            ->assertSee('Aguardando processamento')
            ->assertSee('As versões estão sendo geradas em segundo plano. Atualize a página para ver o resultado.')
            ->assertSee('As versões otimizadas estão sendo geradas em segundo plano. Atualize a página em alguns instantes.')
            ->assertSee('Geração em andamento')
            ->assertDontSee('Ausente: a geração falhou ou não foi concluída')
            ->assertDontSee('data-versao-ausente', false)
            ->assertDontSee(route('admin.fotografias.regenerate-versions', $fotografia), false);

        $this->setStatus($fotografia, StatusDerivacoes::Processando);

        $this->show($admin, $fotografia)
            ->assertSee('data-status-derivacoes="processando"', false)
            ->assertSee('Em processamento')
            ->assertSee('Em geração')
            ->assertDontSee('Ausente: a geração falhou ou não foi concluída');
    }

    public function test_show_page_presents_stalled_failed_and_completed_states(): void
    {
        $this->enableAsync();
        $admin = $this->internalUser();
        $fotografia = $this->photographWithOriginal($admin);

        $this->setStatus($fotografia, StatusDerivacoes::Processando, now()->subSeconds(GerarVersoesOtimizadas::TIMEOUT + 1));
        $this->show($admin, $fotografia)
            ->assertSee('data-status-derivacoes="sem_progresso"', false)
            ->assertSee('Sem progresso')
            ->assertSee('O processamento não avançou nos últimos minutos.')
            ->assertSee(route('admin.fotografias.regenerate-versions', $fotografia), false)
            ->assertDontSee('Geração em andamento');

        $this->setStatus($fotografia, StatusDerivacoes::Falhou);
        $this->show($admin, $fotografia)
            ->assertSee('data-status-derivacoes="falhou"', false)
            ->assertSee('Ausente: a geração falhou ou não foi concluída')
            ->assertSee('Use “Regenerar versões” no menu de ações para tentar de novo a partir do original.')
            ->assertSee(route('admin.fotografias.regenerate-versions', $fotografia), false);

        $this->runWorker();
        $this->show($admin, $fotografia)
            ->assertSee('data-status-derivacoes="concluido"', false)
            ->assertSee('Concluído')
            ->assertDontSee('data-versao-ausente', false)
            ->assertDontSee('data-versao-pendente', false);
    }

    public function test_listing_marks_photographs_still_generating_or_failed(): void
    {
        $this->enableAsync();
        $admin = $this->internalUser();
        $pendente = $this->photographWithOriginal($admin);
        $falhou = $this->photographWithOriginal($admin, UploadedFile::fake()->image('falha.jpg', 2000, 1500));
        $this->setStatus($falhou, StatusDerivacoes::Falhou);

        $this->actingAs($admin)
            ->get(route('admin.fotografias.index'))
            ->assertOk()
            ->assertSee('Gerando')
            ->assertSee('As versões otimizadas estão sendo geradas em segundo plano.')
            ->assertSee('Falhou')
            ->assertSee('A geração das versões otimizadas falhou. Abra a fotografia para regenerar.');

        $this->runWorker();

        $this->actingAs($admin)
            ->get(route('admin.fotografias.index'))
            ->assertDontSee('Gerando')
            ->assertSee(route('admin.arquivos.show', $this->derivatives($pendente)['thumbnail']), false);
    }

    public function test_async_manual_regeneration_queues_a_full_regeneration_audited_for_the_requester(): void
    {
        $this->enableAsync();
        $admin = $this->internalUser();
        $fotografia = $this->photographWithOriginal($admin);
        $this->runWorker();
        $original = $this->original($fotografia);
        $antigas = $this->derivatives($fotografia)->pluck('id')->all();

        $this->actingAs($admin)
            ->post(route('admin.fotografias.regenerate-versions', $fotografia))
            ->assertRedirect(route('admin.fotografias.show', $fotografia))
            ->assertSessionHas('success', 'A regeneração das versões foi enfileirada. Atualize a página em alguns instantes para ver o resultado.');

        $this->assertSame(StatusDerivacoes::Pendente, $this->original($fotografia)->derivacoes_status);
        $this->assertSame($antigas, $this->derivatives($fotografia)->pluck('id')->all());

        // Segundo clique enquanto o job aguarda: nenhum job novo.
        $this->actingAs($admin)
            ->post(route('admin.fotografias.regenerate-versions', $fotografia))
            ->assertSessionHas('warning', 'As versões desta fotografia já estão sendo geradas. Atualize a página em alguns instantes.');
        $this->assertSame(1, DB::table('jobs')->count());

        $this->runWorker();

        $novas = $this->derivatives($fotografia);
        $this->assertSame(StatusDerivacoes::Concluido, $this->original($fotografia)->derivacoes_status);
        $this->assertEmpty(array_intersect($antigas, $novas->pluck('id')->all()));
        $this->assertSame($original->only(['id', 'storage_path', 'sha256']), $this->original($fotografia)->only(['id', 'storage_path', 'sha256']));
        $this->assertDerivativeFilesMatchRecords($fotografia);

        $evento = AuditEvent::query()->where('action', AuditAction::Regenerated)->latest('id')->firstOrFail();
        $this->assertSame(AuditSource::Job, $evento->source);
        $this->assertSame($admin->id, $evento->actor_user_id);
        $this->assertSame('todas', $evento->metadata['mode']);
        $this->assertCount(3, $evento->metadata['derivations_removed']);
    }

    public function test_manual_regeneration_requires_permission_even_while_generating(): void
    {
        $this->enableAsync();
        $fotografia = $this->photographWithOriginal($this->internalUser());
        $inativo = User::factory()->create(['role' => 'admin', 'status' => 'inativo']);

        $resposta = $this->actingAs($inativo)->post(route('admin.fotografias.regenerate-versions', $fotografia));

        $this->assertContains($resposta->status(), [403, 302]);
        $resposta->assertSessionMissing('warning');
        $this->assertSame(1, DB::table('jobs')->count());
    }

    public function test_sync_manual_regeneration_is_unchanged_and_refuses_a_concurrent_run(): void
    {
        $admin = $this->internalUser();
        $fotografia = $this->photographWithOriginal($admin);
        Queue::fake();

        $this->actingAs($admin)
            ->post(route('admin.fotografias.regenerate-versions', $fotografia))
            ->assertSessionHas('success', 'Versões otimizadas regeneradas com sucesso.');
        Queue::assertNothingPushed();

        $this->setStatus($fotografia, StatusDerivacoes::Processando);
        $antes = $this->derivatives($fotografia)->pluck('id')->all();

        $this->actingAs($admin)
            ->post(route('admin.fotografias.regenerate-versions', $fotografia))
            ->assertSessionHas('warning', 'As versões desta fotografia já estão sendo geradas. Atualize a página em alguns instantes.');
        $this->assertSame($antes, $this->derivatives($fotografia)->pluck('id')->all());
    }

    public function test_stalled_generation_can_be_regenerated_again(): void
    {
        $this->enableAsync();
        $admin = $this->internalUser();
        $fotografia = $this->photographWithOriginal($admin);
        // Job perdido (por exemplo, queue:clear) e nenhum progresso desde então.
        DB::table('jobs')->delete();
        $this->setStatus($fotografia, StatusDerivacoes::Pendente, now()->subSeconds(GerarVersoesOtimizadas::TIMEOUT + 1));

        $this->actingAs($admin)
            ->post(route('admin.fotografias.regenerate-versions', $fotografia))
            ->assertSessionHas('success');
        $this->runWorker();

        $this->assertSame(StatusDerivacoes::Concluido, $this->original($fotografia)->derivacoes_status);
        $this->assertDerivativeFilesMatchRecords($fotografia);
    }

    public function test_exhausted_job_is_recovered_by_queue_retry(): void
    {
        $this->enableAsync();
        $this->failVersion('medium', times: 3);
        $fotografia = $this->photographWithOriginal($this->internalUser());

        $this->runAllAttempts();

        $this->assertSame(StatusDerivacoes::Falhou, $this->original($fotografia)->derivacoes_status);
        $this->assertSame(1, DB::table('failed_jobs')->count());

        Artisan::call('queue:retry', ['id' => ['all']]);
        $this->runWorker();

        $this->assertSame(StatusDerivacoes::Concluido, $this->original($fotografia)->derivacoes_status);
        $this->assertSame(['large', 'medium', 'thumbnail'], $this->derivatives($fotografia)->keys()->all());
        $this->assertSame(0, DB::table('failed_jobs')->count());
        $this->assertDerivativeFilesMatchRecords($fotografia);
    }

    public function test_complete_async_flow_with_worker_downtime_failure_and_recovery(): void
    {
        $this->enableAsync();
        $admin = $this->internalUser();
        $consulta = app(ConsultaFotografiasPublicas::class);

        // 1. Worker parado: o cadastro conclui, a foto fica pendente e fora do catálogo.
        $fotografia = $this->photographWithOriginal($admin);
        $this->publish($fotografia);
        $this->travel(10)->minutes();
        $this->assertSame(StatusDerivacoes::Pendente, $this->original($fotografia)->derivacoes_status);
        $this->assertNull($consulta->porIdentificador($fotografia->id));
        $this->show($admin, $fotografia)->assertSee('data-status-derivacoes="sem_progresso"', false);

        // 2. Worker de volta: o job que ficou na fila é processado.
        $this->runWorker();
        $this->assertSame(StatusDerivacoes::Concluido, $this->original($fotografia)->derivacoes_status);
        $this->assertNotNull($consulta->porIdentificador($fotografia->id));

        // 3. Substituição: sai do catálogo até o job gerar as versões do arquivo novo.
        $this->actingAs($admin)->put(route('admin.fotografias.replace-original', $fotografia), [
            'arquivo_original' => UploadedFile::fake()->image('retrato.jpg', 1400, 2100),
        ]);
        $this->assertNull($consulta->porIdentificador($fotografia->id));
        $this->runWorker();
        $this->assertGreaterThan($this->derivatives($fotografia)['large']->width, $this->derivatives($fotografia)['large']->height);
        $this->assertNotNull($consulta->porIdentificador($fotografia->id));

        // 4. Falha definitiva na regeneração: as versões anteriores continuam públicas.
        $this->failVersion(null, times: 100);
        $this->actingAs($admin)->post(route('admin.fotografias.regenerate-versions', $fotografia));
        $this->runAllAttempts();
        $this->assertSame(StatusDerivacoes::Falhou, $this->original($fotografia)->derivacoes_status);
        $this->assertNotNull($consulta->porIdentificador($fotografia->id));
        $this->show($admin, $fotografia)
            ->assertSee('data-status-derivacoes="falhou"', false)
            ->assertSee('A última geração não foi concluída; as versões anteriores foram mantidas.');

        // 5. Causa corrigida: a regeneração manual recupera.
        $this->app->bind(ArquivoStorage::class, LaravelArquivoStorage::class);
        $this->actingAs($admin)->post(route('admin.fotografias.regenerate-versions', $fotografia))->assertSessionHas('success');
        $this->runWorker();
        $this->assertSame(StatusDerivacoes::Concluido, $this->original($fotografia)->derivacoes_status);
        $this->assertDerivativeFilesMatchRecords($fotografia);

        // Auditoria: upload e substituição pela web; gerações pelo job, correlacionadas à operação
        // que as pediu. Cada tentativa da regeneração que falhou registra as versões com falha.
        $eventos = AuditEvent::query()->whereIn('action', [AuditAction::Uploaded, AuditAction::Replaced, AuditAction::Regenerated])->orderBy('id')->get();
        $regeneracao = AuditAction::Regenerated;
        $this->assertSame(
            [AuditAction::Uploaded, $regeneracao, AuditAction::Replaced, $regeneracao, $regeneracao, $regeneracao, $regeneracao, $regeneracao],
            $eventos->pluck('action')->all(),
        );
        $this->assertSame([AuditSource::Web, AuditSource::Job, AuditSource::Web], $eventos->take(3)->pluck('source')->all());
        $this->assertSame([AuditSource::Job], $eventos->slice(3)->pluck('source')->unique()->values()->all());
        $this->assertSame($eventos[0]->correlation_id, $eventos[1]->correlation_id);
        $this->assertSame($eventos[2]->correlation_id, $eventos[3]->correlation_id);
        $this->assertSame([['large', 'medium', 'thumbnail']], $eventos->slice(4, 3)->map(fn (AuditEvent $evento): array => collect($evento->metadata['derivations']['failed_versions'])->sort()->values()->all())->unique()->values()->all());
        $this->assertSame([$admin->id], $eventos->slice(4)->pluck('actor_user_id')->unique()->values()->all());
        $this->assertSame(1, DB::table('failed_jobs')->count());
    }

    public function test_trash_restore_and_force_delete_with_async_generation(): void
    {
        $this->enableAsync();
        $admin = $this->internalUser();
        $restaurada = $this->photographWithOriginal($admin);
        $excluida = $this->photographWithOriginal($admin, UploadedFile::fake()->image('excluida.jpg', 2000, 1500));
        $this->publish($restaurada);

        foreach ([$restaurada, $excluida] as $fotografia) {
            $this->actingAs($admin)->delete(route('admin.fotografias.destroy', $fotografia));
        }
        $this->actingAs($admin)->delete(route('admin.fotografias.force-destroy', $excluida->id));

        $this->runWorker();
        $this->actingAs($admin)->patch(route('admin.fotografias.restore', $restaurada->id));

        $this->assertSame(StatusDerivacoes::Concluido, $this->original($restaurada)->derivacoes_status);
        $this->assertNotNull(app(ConsultaFotografiasPublicas::class)->porIdentificador($restaurada->id));
        $this->assertSame(0, Arquivo::query()->where('item_acervo_id', $excluida->id)->count());
        $this->assertSame([], Storage::disk('local')->allFiles("acervo/originais/{$excluida->id}"));
        $this->assertSame([], Storage::disk('local')->allFiles("acervo/derivados/{$excluida->id}"));
        $this->assertSame(0, DB::table('failed_jobs')->count());
    }

    private function enableAsync(): void
    {
        config(['acervo.optimized_processing.async' => true, 'queue.default' => 'database']);
    }

    private function runWorker(): void
    {
        Artisan::call('queue:work', ['connection' => 'database', '--queue' => 'imagens', '--stop-when-empty' => true, '--sleep' => 0]);
    }

    private function runAllAttempts(): void
    {
        $this->runWorker();

        foreach ([31, 121] as $segundos) {
            $this->travel($segundos)->seconds();
            $this->runWorker();
        }
    }

    private function failVersion(?string $version, int $times): void
    {
        AsyncAdminFailingStorage::$falhasRestantes = $times;
        $this->app->bind(ArquivoStorage::class, fn (): ArquivoStorage => new AsyncAdminFailingStorage(new LaravelArquivoStorage, $version));
    }

    private function setStatus(ItemAcervo $fotografia, StatusDerivacoes $status, mixed $quando = null): void
    {
        Arquivo::query()->where('item_acervo_id', $fotografia->id)->where('versao_arquivo', 'original')->update([
            'derivacoes_status' => $status->value,
            'derivacoes_atualizadas_em' => $quando ?? now(),
        ]);
    }

    private function upload(User $actor, ?UploadedFile $arquivo = null): TestResponse
    {
        return $this->actingAs($actor)->post(route('admin.fotografias.store'), $this->itemAttributes([
            'arquivo_original' => $arquivo ?? UploadedFile::fake()->image('original.jpg', 2400, 1600),
        ]));
    }

    private function photographWithOriginal(User $actor, ?UploadedFile $arquivo = null): ItemAcervo
    {
        $this->upload($actor, $arquivo)->assertSessionHasNoErrors();

        return ItemAcervo::query()->latest('id')->firstOrFail();
    }

    private function show(User $actor, ItemAcervo $fotografia): TestResponse
    {
        return $this->actingAs($actor)->get(route('admin.fotografias.show', $fotografia))->assertOk();
    }

    private function publish(ItemAcervo $fotografia): void
    {
        ItemAcervo::query()->withTrashed()->whereKey($fotografia->id)->update(['status' => 'publicado', 'visibilidade' => 'publico']);
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
 * Falha ao gravar a versão informada (ou todas, com null) nas primeiras N vezes.
 */
final class AsyncAdminFailingStorage implements ArquivoStorage
{
    public static int $falhasRestantes = 0;

    public function __construct(
        private readonly ArquivoStorage $delegate,
        private readonly ?string $version,
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

        if (str_contains($path, $alvo) && self::$falhasRestantes > 0) {
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
