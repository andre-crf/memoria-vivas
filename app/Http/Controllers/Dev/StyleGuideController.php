<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Support\OrdenacaoListagem;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Style Guide interno: mostra os tokens e os componentes reais da aplicação.
 *
 * Só existe fora de produção e só para administradores. A rota é registrada apenas nos ambientes abaixo e o
 * controller confere o ambiente de novo, para o caso de um cache de rotas gerado em
 * outro ambiente. Os exemplos não gravam nada: formulários sem envio ou com GET para
 * a própria página.
 */
class StyleGuideController extends Controller
{
    /** "e2e" é o ambiente dos testes de navegador (Playwright). */
    public const AMBIENTES = ['local', 'testing', 'e2e'];

    public static function disponivel(): bool
    {
        return app()->environment(self::AMBIENTES);
    }

    /** Fora dos ambientes permitidos a página não existe (404); dentro deles, só administradores a veem (403). */
    private function autorizar(Request $request): void
    {
        abort_unless(self::disponivel(), 404);
        abort_unless($request->user()?->isAdmin(), 403);
    }

    public function admin(Request $request): View
    {
        $this->autorizar($request);

        // Dados de exemplo para os componentes reais de listagem: nada é consultado no banco.
        $ordenacao = OrdenacaoListagem::fromRequest(
            $request,
            ['titulo' => 'titulo', 'data' => 'data', 'itens' => 'itens'],
            'titulo',
            comecamDecrescentes: ['itens'],
        );
        $porPagina = 10;
        $total = 87;
        $pagina = min(max($request->integer('pagina', 1), 1), (int) ceil($total / $porPagina));
        $paginacao = new LengthAwarePaginator(range(1, $porPagina), $total, $porPagina, $pagina, [
            'path' => $request->url(),
            'pageName' => 'pagina',
            'fragment' => 'paginacao',
        ]);
        $paginacao->appends($request->except('pagina'));

        return view('dev.style-guide.admin', [
            'ordenacao' => $ordenacao,
            'paginacao' => $paginacao,
            'termo' => (string) $request->query('q', ''),
        ]);
    }

    public function publico(Request $request): View
    {
        $this->autorizar($request);

        return view('dev.style-guide.publico');
    }
}
