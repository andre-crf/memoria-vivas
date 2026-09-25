<?php

namespace App\Http\Controllers\Admin;

use App\Auditing\AuditContextFactory;
use App\Enums\TipoData;
use App\Enums\Visibilidade;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReplaceArquivoOriginalRequest;
use App\Http\Requests\Admin\StoreFotografiaRequest;
use App\Http\Requests\Admin\UpdateFotografiaRequest;
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
use App\Services\Arquivos\SubstituirArquivoOriginal;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

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
        $service->execute(
            actor: $request->user(),
            data: [
                ...$request->payload(),
                ...$request->classificationPayload(),
            ],
            context: $contexts->fromRequest($request),
            arquivoOriginal: $request->file('arquivo_original'),
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

        $service->execute(
            actor: $request->user(),
            item: $fotografia,
            data: [
                ...$request->payload(),
                ...$request->classificationPayload(),
            ],
            context: $contexts->fromRequest($request),
            arquivoOriginal: $request->file('arquivo_original'),
        );

        return redirect()
            ->route('admin.fotografias.show', $fotografia)
            ->with('success', 'Fotografia atualizada com sucesso.');
    }

    public function replaceOriginal(
        ReplaceArquivoOriginalRequest $request,
        ItemAcervo $fotografia,
        SubstituirArquivoOriginal $service,
        AuditContextFactory $contexts,
    ): RedirectResponse {
        $this->ensurePhotograph($fotografia);

        $service->execute(
            actor: $request->user(),
            item: $fotografia,
            file: $request->file('arquivo_original'),
            context: $contexts->fromRequest($request),
        );

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
