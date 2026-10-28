<?php

namespace App\Http\Controllers\Admin;

use App\Auditing\AuditContextFactory;
use App\Auditing\Enums\AuditEntity;
use App\Enums\TipoData;
use App\Enums\Visibilidade;
use App\Http\Controllers\Admin\Concerns\ListagemPesquisavel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReplaceArquivoOriginalRequest;
use App\Http\Requests\Admin\StoreFotografiaRequest;
use App\Http\Requests\Admin\UpdateFotografiaRequest;
use App\Models\Arquivo;
use App\Models\AuditEvent;
use App\Models\ItemAcervo;
use App\Models\User;
use App\Queries\Admin\ConsultaFotografiasAdmin;
use App\Queries\Admin\FiltrosFotografiasAdmin;
use App\Services\Acervo\AtualizarItemAcervo;
use App\Services\Acervo\CriarItemAcervo;
use App\Services\Acervo\ExcluirItemAcervo;
use App\Services\Acervo\ExcluirItemAcervoDefinitivamente;
use App\Services\Acervo\RestaurarItemAcervo;
use App\Services\Arquivos\RegenerarVersoesOtimizadas;
use App\Services\Arquivos\SubstituirArquivoOriginal;
use App\Support\OrdenacaoListagem;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;

class FotografiaController extends Controller
{
    use ListagemPesquisavel;

    public function index(Request $request, ConsultaFotografiasAdmin $consulta): Response
    {
        Gate::authorize('viewAny', ItemAcervo::class);

        $ordenacao = ConsultaFotografiasAdmin::ordenacao($request);

        return $this->listagem('admin.fotografias.index', $request, $ordenacao, fn (FiltrosFotografiasAdmin $filtros) => $consulta->ativas($filtros, $ordenacao));
    }

    public function trashed(Request $request, ConsultaFotografiasAdmin $consulta): Response
    {
        Gate::authorize('restore', new ItemAcervo);

        $ordenacao = ConsultaFotografiasAdmin::ordenacao($request, lixeira: true);

        return $this->listagem('admin.fotografias.trashed', $request, $ordenacao, fn (FiltrosFotografiasAdmin $filtros) => $consulta->excluidas($filtros, $ordenacao));
    }

    public function create(): View
    {
        Gate::authorize('create', ItemAcervo::class);

        return view('admin.fotografias.create', [
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
            'historicoAuditoria' => Gate::allows('viewAudit', User::class)
                ? $this->auditHistory($fotografia)
                : null,
        ]);
    }

    public function edit(ItemAcervo $fotografia): View
    {
        $this->ensurePhotograph($fotografia);
        Gate::authorize('update', $fotografia);

        $fotografia->load(['arquivos', 'categorias', 'assuntos', 'palavrasChave', 'pessoas']);

        return view('admin.fotografias.edit', [
            'fotografia' => $fotografia,
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

    public function regenerateVersions(
        Request $request,
        ItemAcervo $fotografia,
        RegenerarVersoesOtimizadas $service,
        AuditContextFactory $contexts,
    ): RedirectResponse {
        $this->ensurePhotograph($fotografia);

        $resultado = $service->execute(
            actor: $request->user(),
            item: $fotografia,
            context: $contexts->fromRequest($request),
        );

        $falhas = collect($resultado->failedVersions)->map(Arquivo::rotuloVersao(...))->join(', ', ' e ');
        [$tipo, $mensagem] = match (true) {
            ! $resultado->applicable => ['warning', 'O arquivo original não é uma imagem; versões otimizadas não se aplicam.'],
            $falhas === '' => ['success', 'Versões otimizadas regeneradas com sucesso.'],
            $resultado->generated === [] => ['error', "Não foi possível regenerar as versões ({$falhas}). As versões anteriores foram mantidas."],
            default => ['warning', "Versões regeneradas, exceto {$falhas}. As versões anteriores dessas foram mantidas."],
        };

        return redirect()
            ->route('admin.fotografias.show', $fotografia)
            ->with($tipo, $mensagem);
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

    /**
     * Pesquisa inválida responde 422 e é ignorada; status e visibilidade fora das opções são ignorados,
     * como no catálogo público.
     *
     * @param  Closure(FiltrosFotografiasAdmin): LengthAwarePaginator<ItemAcervo>  $consultar
     */
    private function listagem(string $view, Request $request, OrdenacaoListagem $ordenacao, Closure $consultar): Response
    {
        [$termo, $searchError] = $this->pesquisa($request);
        $status = $request->query('status');
        $visibilidade = $request->query('visibilidade');
        $filtros = new FiltrosFotografiasAdmin(
            termo: $termo,
            status: is_string($status) && array_key_exists($status, ItemAcervo::STATUS) ? $status : null,
            visibilidade: is_string($visibilidade) && Visibilidade::tryFrom($visibilidade) !== null ? $visibilidade : null,
        );

        return response()->view($view, [
            'fotografias' => $consultar($filtros),
            'filtros' => $filtros,
            'ordenacao' => $ordenacao,
            'statusOptions' => ItemAcervo::STATUS,
            'visibilidades' => Visibilidade::cases(),
            'searchError' => $searchError,
        ], $searchError !== null ? 422 : 200);
    }

    private function ensurePhotograph(ItemAcervo $fotografia): void
    {
        abort_unless($fotografia->tipo_item === 'fotografia', Response::HTTP_NOT_FOUND);
    }

    private function auditHistory(ItemAcervo $fotografia): LengthAwarePaginator
    {
        return AuditEvent::query()
            ->where(function ($query) use ($fotografia): void {
                $query
                    ->where(function ($itemQuery) use ($fotografia): void {
                        $itemQuery
                            ->where('subject_type', AuditEntity::ItemAcervo->value)
                            ->where('subject_id', (string) $fotografia->id);
                    })
                    ->orWhere(function ($fileQuery) use ($fotografia): void {
                        $fileQuery
                            ->where('subject_type', AuditEntity::Arquivo->value)
                            ->where(function ($itemReference) use ($fotografia): void {
                                $itemReference
                                    ->where('metadata->item_acervo->id', $fotografia->id)
                                    ->orWhere('old_values->item_acervo_id', $fotografia->id)
                                    ->orWhere('new_values->item_acervo_id', $fotografia->id);
                            });
                    });
            })
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'historico_auditoria')
            ->withQueryString();
    }
}
