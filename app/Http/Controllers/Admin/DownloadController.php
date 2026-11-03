<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ItemAcervo;
use App\Models\MotivoDownload;
use App\Models\PerfilDownload;
use App\Models\RegistroDownload;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController extends Controller
{
    private const FUSO_ACERVO = 'America/Sao_Paulo';

    public function __invoke(Request $request): View
    {
        [$query, $filtros] = $this->consulta($request);

        $total = (clone $query)->count();
        $fotografiasDistintas = (clone $query)->distinct()->count('item_acervo_id');
        $motivosAgrupados = (clone $query)
            ->selectRaw('motivo_download_id, COUNT(*) as total')
            ->groupBy('motivo_download_id')
            ->orderByDesc('total')
            ->get();
        $titulosMotivos = MotivoDownload::query()
            ->whereIn('id', $motivosAgrupados->pluck('motivo_download_id'))
            ->pluck('titulo', 'id');
        $perfisAgrupados = (clone $query)
            ->selectRaw('perfil_download_id, COUNT(*) as total')
            ->groupBy('perfil_download_id')
            ->orderByDesc('total')
            ->get();
        $titulosPerfis = PerfilDownload::query()
            ->whereIn('id', $perfisAgrupados->pluck('perfil_download_id'))
            ->pluck('titulo', 'id');
        $fotografiasAgrupadas = (clone $query)
            ->selectRaw('item_acervo_id, COUNT(*) as total')
            ->groupBy('item_acervo_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();
        $fotografiasRanking = ItemAcervo::withTrashed()
            ->whereIn('id', $fotografiasAgrupadas->pluck('item_acervo_id'))
            ->get(['id', 'titulo', 'deleted_at'])
            ->keyBy('id');

        $inicio = isset($filtros['de'])
            ? Carbon::createFromFormat('!Y-m-d', $filtros['de'], self::FUSO_ACERVO)->startOfDay()
            : (isset($filtros['ate'])
                ? Carbon::createFromFormat('!Y-m-d', $filtros['ate'], self::FUSO_ACERVO)->subDays(13)->startOfDay()
                : Carbon::now(self::FUSO_ACERVO)->subDays(13)->startOfDay());
        $fim = isset($filtros['ate'])
            ? Carbon::createFromFormat('!Y-m-d', $filtros['ate'], self::FUSO_ACERVO)->endOfDay()
            : Carbon::now(self::FUSO_ACERVO)->endOfDay();
        if ($inicio->greaterThan($fim)) {
            $fim = $inicio->copy()->endOfDay();
        }
        $dias = (int) $inicio->diffInDays($fim->copy()->startOfDay()) + 1;
        $passo = max(1, (int) ceil($dias / 14));
        $serie = [];
        for ($deslocamento = 0; $deslocamento < $dias; $deslocamento += $passo) {
            $de = $inicio->copy()->addDays($deslocamento);
            $ate = $de->copy()->addDays($passo - 1)->min($fim);
            $serie[] = [
                'rotulo' => $passo === 1 ? $de->format('d/m') : $de->format('d/m').'–'.$ate->format('d/m'),
                'total' => 0,
            ];
        }

        foreach ((clone $query)->whereBetween('created_at', [$inicio->copy()->utc(), $fim->copy()->utc()])->select('created_at')->cursor() as $registro) {
            $dia = $registro->created_at->copy()->timezone(self::FUSO_ACERVO)->startOfDay();
            $indice = intdiv((int) $inicio->diffInDays($dia), $passo);
            if (isset($serie[$indice])) {
                $serie[$indice]['total']++;
            }
        }

        return view('admin.downloads.index', [
            'registros' => (clone $query)
                ->with(['itemAcervo:id,titulo,deleted_at', 'arquivo:id,versao_arquivo', 'perfilDownload:id,titulo', 'motivoDownload:id,titulo'])
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString(),
            'total' => $total,
            'fotografiasDistintas' => $fotografiasDistintas,
            'ultimoDownload' => (clone $query)->orderByDesc('created_at')->first(['created_at'])?->created_at,
            'serie' => $serie,
            'maiorDia' => max(array_column($serie, 'total')),
            'periodoSerie' => $inicio->format('d/m/Y').' a '.$fim->format('d/m/Y'),
            'motivosAgrupados' => $motivosAgrupados,
            'titulosMotivos' => $titulosMotivos,
            'perfisAgrupados' => $perfisAgrupados,
            'titulosPerfis' => $titulosPerfis,
            'fotografiasAgrupadas' => $fotografiasAgrupadas,
            'fotografiasRanking' => $fotografiasRanking,
            'filtros' => ['q' => $filtros['q'] ?? '', 'de' => $filtros['de'] ?? '', 'ate' => $filtros['ate'] ?? ''],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        [$query] = $this->consulta($request);

        return response()->streamDownload(function () use ($query): void {
            $saida = fopen('php://output', 'wb');
            fwrite($saida, "\xEF\xBB\xBF");
            fputcsv($saida, ['Data (Umuarama)', 'Fotografia', 'Registro', 'Solicitante', 'E-mail', 'Perfil', 'Motivo', 'Finalidade', 'Versão'], ';', '"', '');

            (clone $query)
                ->with(['itemAcervo:id,titulo,deleted_at', 'arquivo:id,versao_arquivo', 'perfilDownload:id,titulo', 'motivoDownload:id,titulo'])
                ->chunkById(500, function ($registros) use ($saida): void {
                    foreach ($registros as $registro) {
                        fputcsv($saida, [
                            $registro->created_at?->copy()->timezone(self::FUSO_ACERVO)->format('d/m/Y H:i') ?? '',
                            $this->csvSeguro($registro->itemAcervo?->titulo ?? 'Fotografia removida'),
                            $registro->item_acervo_id,
                            $this->csvSeguro($registro->nome),
                            $this->csvSeguro($registro->email),
                            $this->csvSeguro($registro->perfilDownload?->titulo),
                            $this->csvSeguro($registro->motivoDownload?->titulo),
                            $this->csvSeguro($registro->finalidade),
                            $registro->arquivo ? \App\Models\Arquivo::rotuloVersao($registro->arquivo->versao_arquivo) : '',
                        ], ';', '"', '');
                    }
                });

            fclose($saida);
        }, 'relatorio-downloads-'.Carbon::now(self::FUSO_ACERVO)->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** @return array{0: Builder<RegistroDownload>, 1: array<string, string>} */
    private function consulta(Request $request): array
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $filtros = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'de' => ['nullable', 'date_format:Y-m-d'],
            'ate' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:de'],
        ]);

        $busca = trim($filtros['q'] ?? '');
        $query = RegistroDownload::query();

        if ($busca !== '') {
            $query->where(function (Builder $registros) use ($busca): void {
                $termo = "%{$busca}%";
                $registros
                    ->where('nome', 'like', $termo)
                    ->orWhere('email', 'like', $termo)
                    ->orWhereHas('itemAcervo', fn (Builder $itens) => $itens->where('titulo', 'like', $termo));

                if (preg_match('/^#?(\d+)$/', $busca, $matches) === 1) {
                    $registros->orWhere('item_acervo_id', (int) $matches[1]);
                }
            });
        }
        if (isset($filtros['de'])) {
            $query->where('created_at', '>=', Carbon::createFromFormat('!Y-m-d', $filtros['de'], self::FUSO_ACERVO)->startOfDay()->utc());
        }
        if (isset($filtros['ate'])) {
            $query->where('created_at', '<=', Carbon::createFromFormat('!Y-m-d', $filtros['ate'], self::FUSO_ACERVO)->endOfDay()->utc());
        }

        return [$query, $filtros];
    }

    private function csvSeguro(?string $valor): string
    {
        $valor = str_replace(["\r", "\n"], ' ', (string) $valor);

        return preg_match('/^\s*[=+\-@]/u', $valor) === 1 ? "'".$valor : $valor;
    }
}
