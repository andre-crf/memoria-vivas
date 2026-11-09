<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Arquivo;
use App\Models\ItemAcervo;
use App\Models\User;
use App\Queries\Admin\EstatisticasDownloads;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /** Período do resumo de downloads, em dias contados até hoje (horário de Umuarama). */
    private const DIAS_RESUMO_DOWNLOADS = 30;

    public function __invoke(Request $request, EstatisticasDownloads $estatisticas): View
    {
        $tiposArquivo = Arquivo::query()
            ->selectRaw('tipo_arquivo, COUNT(*) as total')
            ->groupBy('tipo_arquivo')
            ->pluck('total', 'tipo_arquivo')
            ->all();

        $itensPorTipo = ItemAcervo::query()
            ->selectRaw('tipo_item, COUNT(*) as total')
            ->groupBy('tipo_item')
            ->pluck('total', 'tipo_item')
            ->all();

        $itensPorDecada = ItemAcervo::query()
            ->select(['tipo_data', 'ano', 'decada'])
            ->get()
            ->map(fn (ItemAcervo $item): string => $this->decadeBucket($item))
            ->countBy()
            ->sortKeys()
            ->all();

        return view('admin.dashboard', [
            'totalItens' => ItemAcervo::count(),
            'itensPublicados' => ItemAcervo::where('status', 'publicado')->count(),
            'totalFotografias' => ItemAcervo::where('tipo_item', 'fotografia')->count(),
            'totalArquivos' => Arquivo::count(),
            'totalImagens' => Arquivo::where('tipo_arquivo', 'imagem')->count(),
            'totalDocumentos' => Arquivo::where('tipo_arquivo', 'documento')->count(),
            'usuariosAtivos' => User::where('status', 'ativo')->count(),
            'tiposArquivo' => $this->breakdown($tiposArquivo, [
                'imagem' => 'Imagens',
                'documento' => 'Documentos',
                'audio' => 'Áudios',
                'video' => 'Vídeos',
                'outro' => 'Outros',
            ]),
            'itensPorTipo' => $this->breakdown($itensPorTipo, [
                'fotografia' => 'Fotografias',
                'documento' => 'Documentos',
                'artigo' => 'Artigos',
                'jornal' => 'Jornais',
                'outro' => 'Outros',
            ]),
            'itensPorDecada' => $this->breakdown($itensPorDecada),
            'itensRecentes' => ItemAcervo::query()
                ->with([
                    'autor',
                    'arquivos' => fn ($query) => $query->where('versao_arquivo', 'thumbnail'),
                ])
                ->latest('id')
                ->limit(5)
                ->get(),
            // O relatório de downloads é só do admin: para o operador, nada é consultado.
            'resumoDownloads' => $request->user()->isAdmin() ? $this->resumoDownloads($estatisticas) : null,
        ]);
    }

    /**
     * @return array{dias: int, total: int, ultimo: ?Carbon, perfil: ?object, fotografia: ?object, desde: string}
     */
    private function resumoDownloads(EstatisticasDownloads $estatisticas): array
    {
        $desde = Carbon::now(EstatisticasDownloads::FUSO)->subDays(self::DIAS_RESUMO_DOWNLOADS - 1)->format('Y-m-d');
        $periodo = $estatisticas->consulta(de: $desde);

        return [
            'dias' => self::DIAS_RESUMO_DOWNLOADS,
            'desde' => $desde,
            'total' => $estatisticas->total($periodo),
            // O último download de todos, mesmo fora do período.
            'ultimo' => $estatisticas->ultimoDownload($estatisticas->consulta()),
            'perfil' => $estatisticas->perfis($periodo, 1)->first(),
            'fotografia' => $estatisticas->maisBaixadas($periodo, 1)->first(),
        ];
    }

    private function decadeBucket(ItemAcervo $item): string
    {
        if ($item->decada !== null) {
            return 'Década de '.$item->decada;
        }

        if ($item->ano !== null) {
            return 'Década de '.((int) floor($item->ano / 10) * 10);
        }

        return 'Sem data';
    }

    /**
     * @param  array<string, int|string>  $counts
     * @param  array<string, string>  $labels
     * @return Collection<int, array{key: string, label: string, total: int, percent: int}>
     */
    private function breakdown(array $counts, array $labels = []): Collection
    {
        $total = array_sum(array_map('intval', $counts));

        return collect($counts)
            ->map(fn (int|string $count, string $key): array => [
                'key' => $key,
                'label' => $labels[$key] ?? $key,
                'total' => (int) $count,
                'percent' => $total > 0 ? (int) round(((int) $count / $total) * 100) : 0,
            ])
            ->sortByDesc('total')
            ->values();
    }
}
