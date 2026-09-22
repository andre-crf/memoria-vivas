<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TipoData;
use App\Enums\Visibilidade;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFotografiaRequest;
use App\Http\Requests\Admin\UpdateFotografiaRequest;
use App\Models\Arquivo;
use App\Models\Assunto;
use App\Models\Autor;
use App\Models\Categoria;
use App\Models\ItemAcervo;
use App\Models\PalavraChave;
use App\Models\Pessoa;
use App\Support\OptimizedImageVersions;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class FotografiaController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', ItemAcervo::class);

        $fotografias = ItemAcervo::query()
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

    public function store(StoreFotografiaRequest $request): RedirectResponse
    {
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

        DB::transaction(function () use ($request, $originalFileHash): void {
            $fotografia = ItemAcervo::create($request->payload());
            $this->syncClassifications($fotografia, $request->classificationPayload());
            $this->storeOriginalFile($fotografia, $request->file('arquivo_original'), $originalFileHash);
        });

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

    public function update(UpdateFotografiaRequest $request, ItemAcervo $fotografia): RedirectResponse
    {
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

        DB::transaction(function () use ($request, $fotografia, $originalFileHash): void {
            $fotografia->update($request->payload());
            $this->syncClassifications($fotografia, $request->classificationPayload());
            $this->storeOriginalFile($fotografia, $request->file('arquivo_original'), $originalFileHash);
        });

        return redirect()
            ->route('admin.fotografias.show', $fotografia)
            ->with('success', 'Fotografia atualizada com sucesso.');
    }

    public function destroy(ItemAcervo $fotografia): RedirectResponse
    {
        $this->ensurePhotograph($fotografia);
        Gate::authorize('delete', $fotografia);

        $fotografia->delete();

        return redirect()
            ->route('admin.fotografias.index')
            ->with('success', 'Fotografia excluída com sucesso.');
    }

    public function restore(string $fotografia): RedirectResponse
    {
        $fotografia = ItemAcervo::query()
            ->onlyTrashed()
            ->whereKey($fotografia)
            ->firstOrFail();

        $this->ensurePhotograph($fotografia);
        Gate::authorize('restore', $fotografia);

        $fotografia->restore();

        return redirect()
            ->route('admin.fotografias.trashed')
            ->with('success', 'Fotografia restaurada com sucesso.');
    }

    public function forceDestroy(string $fotografia): RedirectResponse
    {
        $fotografia = ItemAcervo::query()
            ->onlyTrashed()
            ->whereKey($fotografia)
            ->firstOrFail();

        $this->ensurePhotograph($fotografia);
        Gate::authorize('forceDelete', $fotografia);

        $fotografia->forceDelete();

        return redirect()
            ->route('admin.fotografias.trashed')
            ->with('success', 'Fotografia excluída permanentemente.');
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

        $extension = $file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'bin';
        $storagePath = $file->storeAs(
            "acervo/originais/{$fotografia->id}",
            Str::uuid()->toString().'.'.$extension,
            'local',
        );
        [$width, $height] = $this->imageDimensions($file);

        $original = $fotografia->arquivos()->create([
            'nome_original' => $file->getClientOriginalName(),
            'provider' => 'local',
            'storage_path' => $storagePath,
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'file_size' => $file->getSize(),
            'tipo_arquivo' => $this->fileType($file),
            'sha256' => $sha256 ?? $this->originalFileHash($file),
            'versao_arquivo' => 'original',
            'width' => $width,
            'height' => $height,
        ]);

        app(OptimizedImageVersions::class)->generate($fotografia, $original);
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
     * @return \Illuminate\Support\Collection<int, \App\Models\Autor>
     */
    private function autorOptions()
    {
        return Autor::query()
            ->orderBy('nome')
            ->get(['id', 'nome', 'tipo']);
    }

    /**
     * @return \Illuminate\Support\Collection<int, \App\Models\Categoria>
     */
    private function categoriaOptions()
    {
        return Categoria::query()
            ->orderBy('titulo')
            ->get(['id', 'titulo']);
    }

    /**
     * @return \Illuminate\Support\Collection<int, \App\Models\Assunto>
     */
    private function assuntoOptions()
    {
        return Assunto::query()
            ->orderBy('titulo')
            ->get(['id', 'titulo']);
    }

    /**
     * @return \Illuminate\Support\Collection<int, \App\Models\PalavraChave>
     */
    private function palavraChaveOptions()
    {
        return PalavraChave::query()
            ->orderBy('termo')
            ->get(['id', 'termo']);
    }

    /**
     * @return \Illuminate\Support\Collection<int, \App\Models\Pessoa>
     */
    private function pessoaOptions()
    {
        return Pessoa::query()
            ->orderBy('nome')
            ->get(['id', 'nome']);
    }

    /**
     * @param  array{categoria_ids: array<int, int>, assunto_ids: array<int, int>, palavra_chave_ids: array<int, int>, pessoa_ids: array<int, int>}  $classifications
     */
    private function syncClassifications(ItemAcervo $fotografia, array $classifications): void
    {
        $fotografia->categorias()->sync($classifications['categoria_ids']);
        $fotografia->assuntos()->sync($classifications['assunto_ids']);
        $fotografia->palavrasChave()->sync($classifications['palavra_chave_ids']);
        $fotografia->pessoas()->sync($classifications['pessoa_ids']);
    }
}
