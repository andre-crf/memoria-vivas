<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RegistroDownload;
use App\Queries\Admin\EstatisticasDownloads;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController extends Controller
{
    private const FUSO_ACERVO = EstatisticasDownloads::FUSO;

    /** Abas do relatório: cada uma é uma página e só consulta o que mostra. A primeira é a padrão. */
    public const ABAS = ['estatisticas' => 'Estatísticas', 'registros' => 'Registros'];

    public function __construct(private readonly EstatisticasDownloads $estatisticas) {}

    public function __invoke(Request $request): View
    {
        [$query, $filtros] = $this->consulta($request);
        $aba = array_key_exists((string) $request->query('aba'), self::ABAS) ? (string) $request->query('aba') : array_key_first(self::ABAS);
        $comum = [
            'aba' => $aba,
            'abas' => self::ABAS,
            'filtros' => ['q' => $filtros['q'] ?? '', 'de' => $filtros['de'] ?? '', 'ate' => $filtros['ate'] ?? ''],
        ];

        if ($aba === 'registros') {
            $registros = (clone $query)
                ->with(['itemAcervo:id,titulo,codigo_referencia,deleted_at', 'arquivo:id,versao_arquivo', 'perfilDownload:id,titulo', 'motivoDownload:id,titulo', 'pais:id,nome,codigo', 'estado:id,sigla', 'municipio:id,nome'])
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString();

            return view('admin.downloads.index', $comum + [
                'registros' => $registros,
                'total' => $registros->total(),
            ]);
        }

        $serie = $this->estatisticas->serie($query, $filtros['de'] ?? null, $filtros['ate'] ?? null);

        return view('admin.downloads.index', $comum + [
            'total' => $this->estatisticas->total($query),
            'fotografiasDistintas' => $this->estatisticas->fotografiasDistintas($query),
            'ultimoDownload' => $this->estatisticas->ultimoDownload($query),
            'serie' => $serie['serie'],
            'maiorDia' => $serie['maiorDia'],
            'periodoSerie' => $serie['periodo'],
            'perfisAgrupados' => $this->estatisticas->perfis($query),
            'motivosAgrupados' => $this->estatisticas->motivos($query),
            'fotografiasAgrupadas' => $this->estatisticas->maisBaixadas($query),
            'paisesAgrupados' => $this->estatisticas->paises($query, 10),
            'estadosAgrupados' => $this->estatisticas->estados($query, 10),
            'municipiosAgrupados' => $this->estatisticas->municipios($query, 10),
            'perfisPorPais' => $this->estatisticas->perfisPorPais($query),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        [$query] = $this->consulta($request);

        return response()->streamDownload(function () use ($query): void {
            $saida = fopen('php://output', 'wb');
            fwrite($saida, "\xEF\xBB\xBF");
            fputcsv($saida, ['Data (Umuarama)', 'Fotografia', 'Registro', 'Código de referência', 'Situação', 'Solicitante', 'E-mail', 'Perfil', 'Motivo', 'Finalidade', 'Versão', 'País', 'Estado', 'Município'], ';', '"', '');

            (clone $query)
                ->with(['itemAcervo:id,titulo,codigo_referencia,deleted_at', 'arquivo:id,versao_arquivo', 'perfilDownload:id,titulo', 'motivoDownload:id,titulo', 'pais:id,nome', 'estado:id,sigla', 'municipio:id,nome'])
                ->chunkById(500, function ($registros) use ($saida): void {
                    foreach ($registros as $registro) {
                        fputcsv($saida, [
                            $registro->created_at?->copy()->timezone(self::FUSO_ACERVO)->format('d/m/Y H:i') ?? '',
                            $this->csvSeguro($registro->tituloFotografia()),
                            $registro->item_acervo_id ?? '',
                            $this->csvSeguro($registro->itemAcervo?->codigo_referencia ?? $registro->codigo_referencia_item),
                            match (true) {
                                $registro->fotografiaExcluida() => 'Excluída definitivamente',
                                $registro->itemAcervo?->trashed() => 'Na lixeira',
                                default => 'No acervo',
                            },
                            $this->csvSeguro($registro->nome),
                            $this->csvSeguro($registro->email),
                            $this->csvSeguro($registro->perfilDownload?->titulo),
                            $this->csvSeguro($registro->motivoDownload?->titulo),
                            $this->csvSeguro($registro->finalidade),
                            $registro->rotuloVersaoEntregue() ?? '',
                            $this->csvSeguro($registro->pais?->nome),
                            $registro->estado?->sigla ?? '',
                            $this->csvSeguro($registro->municipio?->nome),
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

        return [$this->estatisticas->consulta($filtros['q'] ?? '', $filtros['de'] ?? null, $filtros['ate'] ?? null), $filtros];
    }

    private function csvSeguro(?string $valor): string
    {
        $valor = str_replace(["\r", "\n"], ' ', (string) $valor);

        return preg_match('/^\s*[=+\-@]/u', $valor) === 1 ? "'".$valor : $valor;
    }
}
