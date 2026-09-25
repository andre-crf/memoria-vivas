<?php

namespace Tests\Feature;

use App\Auditing\AuditContext;
use App\Auditing\Enums\AuditAction;
use App\Auditing\Enums\AuditEntity;
use App\Auditing\Enums\AuditSource;
use App\Enums\Visibilidade;
use App\Models\Arquivo;
use App\Models\AuditEvent;
use App\Models\ItemAcervo;
use App\Models\User;
use App\Services\Acervo\CriarItemAcervo;
use App\Services\Arquivos\Contracts\ArquivoStorage;
use App\Services\Arquivos\LaravelArquivoStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use JsonException;
use RuntimeException;
use Tests\TestCase;

class AuditArquivoIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_audits_original_and_item_with_the_same_context(): void
    {
        Storage::fake('local');
        $actor = $this->internalUser('operador');

        $this->actingAs($actor)
            ->post(route('admin.fotografias.store'), $this->payload([
                'arquivo_original' => UploadedFile::fake()->image('memoria.jpg', 640, 480),
            ]))
            ->assertRedirect(route('admin.fotografias.index'));

        $original = Arquivo::query()->where('versao_arquivo', 'original')->sole();
        $events = AuditEvent::query()->orderBy('id')->get();
        $itemEvent = $events->firstWhere('subject_type', AuditEntity::ItemAcervo);
        $fileEvent = $events->firstWhere('subject_type', AuditEntity::Arquivo);

        $this->assertCount(2, $events);
        $this->assertSame(AuditAction::Uploaded, $fileEvent->action);
        $this->assertSame((string) $original->id, $fileEvent->subject_id);
        $this->assertSame($itemEvent->request_id, $fileEvent->request_id);
        $this->assertSame($itemEvent->correlation_id, $fileEvent->correlation_id);
        $this->assertNull($fileEvent->old_values);
        $this->assertSame([
            'item_acervo_id',
            'nome_original',
            'provider',
            'external_file_id',
            'storage_path',
            'mime_type',
            'file_size',
            'tipo_arquivo',
            'sha256',
            'versao_arquivo',
            'width',
            'height',
        ], array_keys($fileEvent->new_values));
        $this->assertSame('arquivo_original_upload', $fileEvent->metadata['operation']);
        $this->assertCount(3, $fileEvent->metadata['derivations']['generated']);
        $this->assertSame([], $fileEvent->metadata['derivations']['failed_versions']);
        $this->assertSame(1, $events->where('subject_type', AuditEntity::Arquivo)->count());

        $serialized = json_encode([$fileEvent->new_values, $fileEvent->metadata]);
        $this->assertStringNotContainsString('password', $serialized);
        $this->assertStringNotContainsString('token', $serialized);
        $this->assertStringNotContainsString('credential', $serialized);
    }

    public function test_upload_during_unchanged_edit_generates_only_file_event(): void
    {
        Storage::fake('local');
        $actor = $this->internalUser();
        $item = $this->photograph();

        $this->actingAs($actor)
            ->put(route('admin.fotografias.update', $item), $this->payload([
                'arquivo_original' => UploadedFile::fake()->create('documento.pdf', 20, 'application/pdf'),
            ]))
            ->assertRedirect(route('admin.fotografias.show', $item));

        $event = AuditEvent::query()->sole();

        $this->assertSame(AuditEntity::Arquivo, $event->subject_type);
        $this->assertSame(AuditAction::Uploaded, $event->action);
        $this->assertFalse($event->metadata['derivations']['applicable']);
        $this->assertSame([], $event->metadata['derivations']['generated']);
    }

    public function test_replacement_preserves_original_id_and_consolidates_derivations(): void
    {
        Storage::fake('local');
        $actor = $this->internalUser();

        $this->actingAs($actor)
            ->post(route('admin.fotografias.store'), $this->payload([
                'arquivo_original' => UploadedFile::fake()->image('antiga.jpg', 640, 480),
            ]));

        $item = ItemAcervo::query()->sole();
        $original = $item->arquivos()->where('versao_arquivo', 'original')->sole();
        $originalId = $original->id;
        DB::table(AuditEvent::TABLE)->delete();

        $this->actingAs($actor)
            ->put(route('admin.fotografias.replace-original', $item), [
                'arquivo_original' => UploadedFile::fake()->create('nova.pdf', 20, 'application/pdf'),
            ])
            ->assertRedirect(route('admin.fotografias.show', $item));

        $event = AuditEvent::query()->sole();
        $current = $item->arquivos()->sole();

        $this->assertSame($originalId, $current->id);
        $this->assertSame(AuditAction::Replaced, $event->action);
        $this->assertSame((string) $originalId, $event->subject_id);
        $this->assertSame('arquivo_original_replace', $event->metadata['operation']);
        $this->assertCount(3, $event->metadata['derivations_removed']);
        $this->assertFalse($event->metadata['derivations']['applicable']);
        $this->assertSame('antiga.jpg', $event->old_values['nome_original']);
        $this->assertSame('nova.pdf', $event->new_values['nome_original']);
    }

    public function test_derivation_failure_keeps_original_and_records_only_safe_summary(): void
    {
        Storage::fake('local');
        Log::spy();
        $this->app->bind(
            ArquivoStorage::class,
            fn (): ArquivoStorage => new FailingDerivativeStorage(new LaravelArquivoStorage),
        );
        config(['acervo.optimized_versions' => [
            'thumbnail' => ['max_dimension' => 320],
        ]]);

        $this->actingAs($this->internalUser())
            ->post(route('admin.fotografias.store'), $this->payload([
                'arquivo_original' => UploadedFile::fake()->image('original.jpg', 640, 480),
            ]))
            ->assertRedirect(route('admin.fotografias.index'));

        $original = Arquivo::query()->sole();
        $event = AuditEvent::query()->where('subject_type', AuditEntity::Arquivo)->sole();

        $this->assertTrue($original->isOriginal());
        Storage::disk('local')->assertExists($original->storage_path);
        $this->assertSame(['thumbnail'], $event->metadata['derivations']['failed_versions']);
        $this->assertArrayNotHasKey('error', $event->metadata['derivations']);
        $this->assertArrayNotHasKey('erro', $event->metadata['derivations']);
        Log::shouldHaveReceived('warning')->withArgs(
            fn (string $message, array $context): bool => $message === 'Falha ao gerar versão otimizada da fotografia.'
                && $context['versao_arquivo'] === 'thumbnail',
        )->once();
    }

    public function test_audit_failure_rolls_back_database_and_removes_new_storage_objects(): void
    {
        Storage::fake('local');
        $actor = $this->internalUser();
        $context = AuditContext::forUser(
            $actor,
            AuditSource::Web,
            (string) Str::uuid(),
            (string) Str::uuid(),
        );

        try {
            app(CriarItemAcervo::class)->execute(
                actor: $actor,
                data: [
                    'tipo_item' => 'fotografia',
                    'titulo' => "Inválida \xB1",
                    'tipo_data' => 'ano',
                    'ano' => 1980,
                    'estado_conservacao' => 'bom',
                    'status' => 'rascunho',
                    'visibilidade' => Visibilidade::Privado,
                ],
                context: $context,
                arquivoOriginal: UploadedFile::fake()->image('rollback.jpg', 640, 480),
            );

            $this->fail('A gravação da auditoria deveria rejeitar JSON inválido.');
        } catch (JsonException) {
            // A compensação do storage ocorre depois do rollback da transação.
        }

        $this->assertDatabaseCount('item_acervos', 0);
        $this->assertDatabaseCount('arquivos', 0);
        $this->assertDatabaseCount('audit_events', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_original_storage_failure_rolls_back_everything_and_cleans_partial_file(): void
    {
        Storage::fake('local');
        $this->app->bind(
            ArquivoStorage::class,
            fn (): ArquivoStorage => new FailingOriginalStorage(new LaravelArquivoStorage),
        );
        $actor = $this->internalUser();
        $context = AuditContext::forUser($actor, AuditSource::Web, (string) Str::uuid());

        try {
            app(CriarItemAcervo::class)->execute(
                actor: $actor,
                data: [
                    'tipo_item' => 'fotografia',
                    'titulo' => 'Falha no original',
                    'status' => 'rascunho',
                    'visibilidade' => Visibilidade::Privado,
                ],
                context: $context,
                arquivoOriginal: UploadedFile::fake()->image('parcial.jpg', 640, 480),
            );

            $this->fail('A falha do armazenamento deveria interromper a operação.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Falha simulada ao armazenar o original.', $exception->getMessage());
        }

        $this->assertDatabaseCount('item_acervos', 0);
        $this->assertDatabaseCount('arquivos', 0);
        $this->assertDatabaseCount('audit_events', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_post_commit_cleanup_failure_keeps_database_and_audit_and_logs_recovery_data(): void
    {
        Storage::fake('local');
        $actor = $this->internalUser();

        $this->actingAs($actor)
            ->post(route('admin.fotografias.store'), $this->payload([
                'arquivo_original' => UploadedFile::fake()->image('antiga.jpg', 640, 480),
            ]));

        $item = ItemAcervo::query()->sole();
        $oldPaths = $item->arquivos()->pluck('storage_path')->all();
        DB::table(AuditEvent::TABLE)->delete();
        Log::spy();
        $this->app->bind(
            ArquivoStorage::class,
            fn (): ArquivoStorage => new FailingCleanupStorage(new LaravelArquivoStorage),
        );

        $this->actingAs($actor)
            ->put(route('admin.fotografias.replace-original', $item), [
                'arquivo_original' => UploadedFile::fake()->create('nova.pdf', 20, 'application/pdf'),
            ])
            ->assertRedirect(route('admin.fotografias.show', $item));

        $current = $item->arquivos()->sole();
        $event = AuditEvent::query()->sole();

        $this->assertSame('nova.pdf', $current->nome_original);
        $this->assertSame(AuditAction::Replaced, $event->action);
        Storage::disk('local')->assertExists($current->storage_path);

        foreach ($oldPaths as $oldPath) {
            Storage::disk('local')->assertExists($oldPath);
        }

        Log::shouldHaveReceived('warning')->withArgs(
            fn (string $message, array $context): bool => $message === 'A limpeza de arquivos do acervo não foi concluída.'
                && $context['provider'] === 'local'
                && $context['phase'] === 'post_commit'
                && collect($context['paths'])->sort()->values()->all() === collect($oldPaths)->sort()->values()->all(),
        )->once();
    }

    public function test_force_delete_audits_and_removes_original_and_derivations_after_commit(): void
    {
        Storage::fake('local');
        $actor = $this->internalUser();

        $this->actingAs($actor)
            ->post(route('admin.fotografias.store'), $this->payload([
                'arquivo_original' => UploadedFile::fake()->image('excluir.jpg', 640, 480),
            ]));

        $item = ItemAcervo::query()->sole();
        $paths = $item->arquivos()->pluck('storage_path')->all();
        DB::table(AuditEvent::TABLE)->delete();
        $item->delete();

        $this->actingAs($actor)
            ->delete(route('admin.fotografias.force-destroy', $item->id))
            ->assertRedirect(route('admin.fotografias.trashed'));

        $itemEvent = AuditEvent::query()->where('subject_type', AuditEntity::ItemAcervo)->sole();
        $fileEvent = AuditEvent::query()->where('subject_type', AuditEntity::Arquivo)->sole();

        foreach ($paths as $path) {
            Storage::disk('local')->assertMissing($path);
        }

        $this->assertSame(AuditAction::ForceDeleted, $itemEvent->action);
        $this->assertSame(AuditAction::Deleted, $fileEvent->action);
        $this->assertCount(3, $fileEvent->metadata['derivations_removed']);
        $this->assertSame($itemEvent->request_id, $fileEvent->request_id);
        $this->assertSame($itemEvent->correlation_id, $fileEvent->correlation_id);
    }

    public function test_force_delete_audits_each_remaining_derivation_when_original_is_missing(): void
    {
        Storage::fake('local');
        $actor = $this->internalUser();
        $item = $this->photograph();
        $arquivos = collect(['thumbnail', 'medium'])->map(function (string $version) use ($item): Arquivo {
            $path = "acervo/derivados/{$item->id}/{$version}.jpg";
            Storage::disk('local')->put($path, $version);

            return $item->arquivos()->create([
                'provider' => 'local',
                'storage_path' => $path,
                'mime_type' => 'image/jpeg',
                'file_size' => strlen($version),
                'tipo_arquivo' => 'imagem',
                'sha256' => hash('sha256', $version),
                'versao_arquivo' => $version,
                'width' => 100,
                'height' => 100,
            ]);
        });
        $item->delete();

        $this->actingAs($actor)
            ->delete(route('admin.fotografias.force-destroy', $item->id))
            ->assertRedirect(route('admin.fotografias.trashed'));

        $fileEvents = AuditEvent::query()
            ->where('subject_type', AuditEntity::Arquivo)
            ->orderBy('subject_id')
            ->get();

        $this->assertCount(2, $fileEvents);
        $this->assertSame(
            $arquivos->pluck('id')->map(fn (int $id): string => (string) $id)->all(),
            $fileEvents->pluck('subject_id')->all(),
        );
        $this->assertSame(
            ['arquivo_orphan_delete'],
            $fileEvents->pluck('metadata.operation')->unique()->values()->all(),
        );

        foreach ($arquivos as $arquivo) {
            Storage::disk('local')->assertMissing($arquivo->storage_path);
        }
    }

    private function internalUser(string $role = 'admin'): User
    {
        return User::factory()->create(['role' => $role, 'status' => 'ativo']);
    }

    private function photograph(): ItemAcervo
    {
        return ItemAcervo::create([
            'titulo' => 'Praça central',
            'tipo_item' => 'fotografia',
            'tipo_data' => 'ano',
            'ano' => 1980,
            'estado_conservacao' => 'bom',
            'status' => 'rascunho',
            'visibilidade' => Visibilidade::Privado,
        ]);
    }

    /** @return array<string, mixed> */
    private function payload(array $override = []): array
    {
        return $override + [
            'titulo' => 'Praça central',
            'tipo_data' => 'ano',
            'ano' => 1980,
            'estado_conservacao' => 'bom',
            'status' => 'rascunho',
            'visibilidade' => Visibilidade::Privado->value,
        ];
    }
}

final readonly class FailingDerivativeStorage implements ArquivoStorage
{
    public function __construct(private ArquivoStorage $delegate) {}

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
        if (str_contains($path, 'acervo/derivados/')) {
            throw new RuntimeException('Falha técnica que não deve entrar no evento.');
        }

        return $this->delegate->absolutePath($provider, $path);
    }

    public function delete(string $provider, array $paths): bool
    {
        return $this->delegate->delete($provider, $paths);
    }
}

final readonly class FailingOriginalStorage implements ArquivoStorage
{
    public function __construct(private ArquivoStorage $delegate) {}

    public function storeUploaded(string $provider, UploadedFile $file, string $directory, string $filename): string
    {
        $this->delegate->storeUploaded($provider, $file, $directory, $filename);

        throw new RuntimeException('Falha simulada ao armazenar o original.');
    }

    public function makeDirectory(string $provider, string $directory): void
    {
        $this->delegate->makeDirectory($provider, $directory);
    }

    public function absolutePath(string $provider, string $path): string
    {
        return $this->delegate->absolutePath($provider, $path);
    }

    public function delete(string $provider, array $paths): bool
    {
        return $this->delegate->delete($provider, $paths);
    }
}

final readonly class FailingCleanupStorage implements ArquivoStorage
{
    public function __construct(private ArquivoStorage $delegate) {}

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
        return $this->delegate->absolutePath($provider, $path);
    }

    public function delete(string $provider, array $paths): bool
    {
        return false;
    }
}
