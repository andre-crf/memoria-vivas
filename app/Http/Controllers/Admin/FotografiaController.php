<?php

namespace App\Http\Controllers\Admin;

use App\Auditing\AuditContextFactory;
use App\Enums\TipoData;
use App\Enums\Visibilidade;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReplaceArquivoOriginalRequest;
use App\Http\Requests\Admin\StoreFotografiaRequest;
use App\Http\Requests\Admin\UpdateFotografiaRequest;
use App\Models\Arquivo;
use App\Models\Assunto;
use App\Models\Autor;
use App\Models\Categoria;
use App\Models\ItemAcervo;
use App\Models\PalavraChave;
use App\Models\Pessoa;
use App\Services\Acervo\AtualizarItemAcervo;
use App\Services\Acervo\CriarItemAcervo;
use App\Services\Acervo\ExcluirItemAcervo;
use App\Services\Acervo\ExcluirItemAcervoDefinitivamente;
use App\Services\Acervo\RestaurarItemAcervo;
use App\Support\OptimizedImageVersions;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FotografiaController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', ItemAcervo::class);

        $fotografias = ItemAcervo::query()
            ->with(['arquivos' => fn ($query) => $query->where('versao_arquivo', 'thumbnail')])
            ->where('tipo_item', 'fotografia')
            ->latest('id')
            ->paginate(15);

        return view('admin.fotografias.index', [
            'fotografias' => $fotografias,
        ]);
    }

    public function trashed(): View
    {
        Gate::authorize('restore', new ItemAcervo);

        $fotografias = ItemAcervo::query()
            ->onlyTrashed()
            ->where('tipo_item', 'fotografia')
            ->with('excluidoPor')
            ->latest('deleted_at')
            ->paginate(15);

        return view('admin.fotografias.trashed', [
            'fotografias' => $fotografias,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', ItemAcervo::class);

        return view('admin.fotografias.create', [
            'autorOptions' => $this->autorOptions(),
            'categoriaOptions' => $this->categoriaOptions(),
            'assuntoOptions' => $this->assuntoOptions(),
            'palavraChaveOptions' => $this->palavraChaveOptions(),
            'pessoaOptions' => $this->pessoaOptions(),
            'tipoDataOptions' => TipoData::cases(),
            'estadoConservacaoOptions' => ItemAcervo::ESTADOS_CONSERVACAO,
            'statusOptions' => ItemAcervo::STATUS,
            'visibilidadeOptions' => Visibilidade::cases(),
        ]);
    }

    public function store(
        StoreFotografiaRequest $request,
        CriarItemAcervo $service,
        AuditContextFactory $contexts,
    ): RedirectResponse {
        $originalFileHash = null;

        if ($request->hasFile('arquivo_original')) {
            Gate::authorize('uploadOriginal', Arquivo::class);

            $originalFileHash = $this->originalFileHash($request->file('arquivo_original'));

            if ($duplicate = $this->duplicateOriginalFile($originalFileHash)) {
                return back()
                    ->withErrors(['arquivo_original' => $this->duplicateOriginalFileMessage($duplicate)])
                    ->withInput();
            }
        }

        $service->execute(
            actor: $request->user(),
            data: [
                ...$request->payload(),
                ...$request->classificationPayload(),
            ],
            context: $contexts->fromRequest($request),
            afterPersist: fn (ItemAcervo $fotografia) => $this->storeOriginalFile(
                $fotografia,
                $request->file('arquivo_original'),
                $originalFileHash,
            ),
        );

        return redirect()
            ->route('admin.fotografias.index')
            ->with('success', 'Fotografia cadastrada com sucesso.');
    }

    public function show(ItemAcervo $fotografia): View
    {
        $this->ensurePhotograph($fotografia);
        Gate::authorize('view', $fotografia);

        $fotografia->load([
            'arquivos',
            'assuntos',
            'autor',
            'categorias',
            'colecoes',
            'conjuntosContextuais',
            'criadoPor',
            'atualizadoPor',
            'palavrasChave',
            'pessoas',
        ]);

        return view('admin.fotografias.show', [
            'fotografia' => $fotografia,
        ]);
    }

    public function edit(ItemAcervo $fotografia): View
    {
        $this->ensurePhotograph($fotografia);
        Gate::authorize('update', $fotografia);

        $fotografia->load(['arquivos', 'categorias', 'assuntos', 'palavrasChave', 'pessoas']);

        return view('admin.fotografias.edit', [
            'fotografia' => $fotografia,
            'autorOptions' => $this->autorOptions(),
            'categoriaOptions' => $this->categoriaOptions(),
            'assuntoOptions' => $this->assuntoOptions(),
            'palavraChaveOptions' => $this->palavraChaveOptions(),
            'pessoaOptions' => $this->pessoaOptions(),
            'tipoDataOptions' => TipoData::cases(),
            'estadoConservacaoOptions' => ItemAcervo::ESTADOS_CONSERVACAO,
            'statusOptions' => ItemAcervo::STATUS,
            'visibilidadeOptions' => Visibilidade::cases(),
        ]);
    }

    public function update(
        UpdateFotografiaRequest $request,
        ItemAcervo $fotografia,
        AtualizarItemAcervo $service,
        AuditContextFactory $contexts,
    ): RedirectResponse {
        $this->ensurePhotograph($fotografia);
        $originalFileHash = null;

        if ($request->hasFile('arquivo_original')) {
            Gate::authorize('uploadOriginal', Arquivo::class);

            if ($fotografia->arquivos()->where('versao_arquivo', 'original')->exists()) {
                return back()
                    ->withErrors(['arquivo_original' => 'Esta fotografia já possui arquivo original vinculado.'])
                    ->withInput();
            }

            $originalFileHash = $this->originalFileHash($request->file('arquivo_original'));

            if ($duplicate = $this->duplicateOriginalFile($originalFileHash, $fotografia)) {
                return back()
                    ->withErrors(['arquivo_original' => $this->duplicateOriginalFileMessage($duplicate)])
                    ->withInput();
            }
        }

        $service->execute(
            actor: $request->user(),
            item: $fotografia,
            data: [
                ...$request->payload(),
                ...$request->classificationPayload(),
            ],
            context: $contexts->fromRequest($request),
            afterPersist: fn (ItemAcervo $fotografia) => $this->storeOriginalFile(
                $fotografia,
                $request->file('arquivo_original'),
                $originalFileHash,
            ),
        );

        return redirect()
            ->route('admin.fotografias.show', $fotografia)
            ->with('success', 'Fotografia atualizada com sucesso.');
    }

    public function replaceOriginal(ReplaceArquivoOriginalRequest $request, ItemAcervo $fotografia): RedirectResponse
    {
        $this->ensurePhotograph($fotografia);

        $original = $fotografia->arquivos()
            ->where('versao_arquivo', 'original')
            ->firstOrFail();

        Gate::authorize('replaceOriginal', $original);

        /** @var UploadedFile $file */
        $file = $request->file('arquivo_original');
        $originalFileHash = $this->originalFileHash($file);

        if ($duplicate = $this->duplicateOriginalFile($originalFileHash, $fotografia)) {
            return back()
                ->withErrors(['arquivo_original' => $this->duplicateOriginalFileMessage($duplicate)])
                ->withInput();
        }

        $oldStoragePaths = DB::transaction(function () use ($file, $fotografia, $original, $originalFileHash): array {
            $derivadas = $fotografia->arquivos()
                ->whereIn('versao_arquivo', array_keys(config('acervo.optimized_versions')))
                ->get();

            $oldStoragePaths = $derivadas
                ->pluck('storage_path')
                ->push($original->storage_path)
                ->filter()
                ->values()
                ->all();

            $derivadas->each->delete();

            $original->update($this->originalFileAttributes($fotografia, $file, $originalFileHash));

            app(OptimizedImageVersions::class)->generate($fotografia, $original->refresh());

            return $oldStoragePaths;
        });

        Storage::disk('local')->delete($oldStoragePaths);

        return redirect()
            ->route('admin.fotografias.show', $fotografia)
            ->with('success', 'Arquivo original substituído com sucesso.');
    }

    public function destroy(
        Request $request,
        ItemAcervo $fotografia,
        ExcluirItemAcervo $service,
        AuditContextFactory $contexts,
    ): RedirectResponse {
        $this->ensurePhotograph($fotografia);

        $service->execute(
            actor: $request->user(),
            item: $fotografia,
            context: $contexts->fromRequest($request),
        );

        return redirect()
            ->route('admin.fotografias.index')
            ->with('success', 'Fotografia excluída com sucesso.');
    }

    public function restore(
        Request $request,
        string $fotografia,
        RestaurarItemAcervo $service,
        AuditContextFactory $contexts,
    ): RedirectResponse {
        $fotografia = $this->trashedPhotograph($fotografia);

        $service->execute(
            actor: $request->user(),
            item: $fotografia,
            context: $contexts->fromRequest($request),
        );

        return redirect()
            ->route('admin.fotografias.trashed')
            ->with('success', 'Fotografia restaurada com sucesso.');
    }

    public function forceDestroy(
        Request $request,
        string $fotografia,
        ExcluirItemAcervoDefinitivamente $service,
        AuditContextFactory $contexts,
    ): RedirectResponse {
        $fotografia = $this->trashedPhotograph($fotografia);

        $service->execute(
            actor: $request->user(),
            item: $fotografia,
            context: $contexts->fromRequest($request),
        );

        return redirect()
            ->route('admin.fotografias.trashed')
            ->with('success', 'Fotografia excluída permanentemente.');
    }

    private function trashedPhotograph(string $fotografia): ItemAcervo
    {
        $fotografia = ItemAcervo::query()
            ->onlyTrashed()
            ->whereKey($fotografia)
            ->firstOrFail();

        $this->ensurePhotograph($fotografia);

        return $fotografia;
    }

    private function ensurePhotograph(ItemAcervo $fotografia): void
    {
        abort_unless($fotografia->tipo_item === 'fotografia', Response::HTTP_NOT_FOUND);
    }

    private function storeOriginalFile(ItemAcervo $fotografia, ?UploadedFile $file, ?string $sha256 = null): void
    {
        if (! $file instanceof UploadedFile) {
            return;
        }

        $original = $fotografia->arquivos()->create(
            $this->originalFileAttributes($fotografia, $file, $sha256 ?? $this->originalFileHash($file))
        );

        app(OptimizedImageVersions::class)->generate($fotografia, $original);
    }

    /**
     * @return array<string, mixed>
     */
    private function originalFileAttributes(ItemAcervo $fotografia, UploadedFile $file, string $sha256): array
    {
        $extension = $file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'bin';
        $storagePath = $file->storeAs(
            "acervo/originais/{$fotografia->id}",
            Str::uuid()->toString().'.'.$extension,
            'local',
        );
        [$width, $height] = $this->imageDimensions($file);

        return [
            'nome_original' => $file->getClientOriginalName(),
            'provider' => 'local',
            'storage_path' => $storagePath,
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'file_size' => $file->getSize(),
            'tipo_arquivo' => $this->fileType($file),
            'sha256' => $sha256,
            'versao_arquivo' => 'original',
            'width' => $width,
            'height' => $height,
        ];
    }

    private function originalFileHash(UploadedFile $file): string
    {
        return (string) hash_file('sha256', $file->getRealPath());
    }

    private function duplicateOriginalFile(string $sha256, ?ItemAcervo $except = null): ?Arquivo
    {
        return Arquivo::query()
            ->with(['itemAcervo' => fn ($query) => $query->withTrashed()])
            ->where('sha256', $sha256)
            ->where('versao_arquivo', 'original')
            ->when($except, fn ($query) => $query->where('item_acervo_id', '!=', $except->id))
            ->oldest('id')
            ->first();
    }

    private function duplicateOriginalFileMessage(Arquivo $arquivo): string
    {
        $item = $arquivo->itemAcervo;

        if (! $item instanceof ItemAcervo) {
            return 'Este arquivo parece já estar cadastrado em outro item do acervo.';
        }

        return "Este arquivo parece já estar cadastrado na fotografia \"{$item->titulo}\" (#{$item->id}).";
    }

    /**
     * @return array{0: int|null, 1: int|null}
     */
    private function imageDimensions(UploadedFile $file): array
    {
        if (! str_starts_with($file->getMimeType() ?: '', 'image/')) {
            return [null, null];
        }

        $dimensions = @getimagesize($file->getRealPath());

        return [
            $dimensions[0] ?? null,
            $dimensions[1] ?? null,
        ];
    }

    private function fileType(UploadedFile $file): string
    {
        $mimeType = $file->getMimeType() ?: '';

        return match (true) {
            str_starts_with($mimeType, 'image/') => 'imagem',
            $mimeType === 'application/pdf' => 'documento',
            str_starts_with($mimeType, 'audio/') => 'audio',
            str_starts_with($mimeType, 'video/') => 'video',
            default => 'outro',
        };
    }

    /**
     * @return Collection<int, Autor>
     */
    private function autorOptions()
    {
        return Autor::query()
            ->orderBy('nome')
            ->get(['id', 'nome', 'tipo']);
    }

    /**
     * @return Collection<int, Categoria>
     */
    private function categoriaOptions()
    {
        return Categoria::query()
            ->orderBy('titulo')
            ->get(['id', 'titulo']);
    }

    /**
     * @return Collection<int, Assunto>
     */
    private function assuntoOptions()
    {
        return Assunto::query()
            ->orderBy('titulo')
            ->get(['id', 'titulo']);
    }

    /**
     * @return Collection<int, PalavraChave>
     */
    private function palavraChaveOptions()
    {
        return PalavraChave::query()
            ->orderBy('termo')
            ->get(['id', 'termo']);
    }

    /**
     * @return Collection<int, Pessoa>
     */
    private function pessoaOptions()
    {
        return Pessoa::query()
            ->orderBy('nome')
            ->get(['id', 'nome']);
    }
}
