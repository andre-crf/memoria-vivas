<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use App\Models\Arquivo;
use App\Models\Estado;
use App\Models\MotivoDownload;
use App\Models\Municipio;
use App\Models\Pais;
use App\Models\PerfilDownload;
use App\Models\RegistroDownload;
use App\Queries\Publico\ConsultaFotografiasPublicas;
use App\Services\Arquivos\Contracts\ArquivoStorage;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DownloadFotografiaController extends Controller
{
    public function create(string $fotografia, ConsultaFotografiasPublicas $consulta): View
    {
        $item = $consulta->detalhePorIdentificador($fotografia);
        abort_unless($item, Response::HTTP_NOT_FOUND);

        return view('public.download-fotografia', [
            'fotografia' => $item,
            'imagem' => $this->imagemParaDownload($item->arquivos),
            'motivos' => MotivoDownload::query()->where('ativo', true)->orderBy('titulo')->get(['id', 'titulo']),
            'perfis' => PerfilDownload::query()->where('ativo', true)->orderBy('titulo')->get(['id', 'titulo']),
            // Brasil primeiro; os demais em ordem alfabética.
            'paises' => Pais::query()
                ->where('ativo', true)
                ->orderByRaw("CASE WHEN codigo = 'BR' THEN 0 ELSE 1 END")
                ->orderBy('nome')
                ->get(['id', 'codigo', 'nome']),
            'estados' => Estado::query()->orderBy('nome')->get(['id', 'sigla', 'nome']),
            // Depois de um erro, a cidade escolhida volta já com a lista do estado.
            'municipios' => filter_var(old('estado_id'), FILTER_VALIDATE_INT)
                ? Municipio::query()->where('estado_id', (int) old('estado_id'))->orderBy('nome')->get(['id', 'nome'])
                : collect(),
        ]);
    }

    public function store(
        Request $request,
        string $fotografia,
        ConsultaFotografiasPublicas $consulta,
        ArquivoStorage $storage,
    ): BinaryFileResponse {
        $item = $consulta->detalhePorIdentificador($fotografia);
        abort_unless($item, Response::HTTP_NOT_FOUND);

        $imagem = $this->imagemParaDownload($item->arquivos);

        try {
            $path = $storage->absolutePath($imagem->provider, $imagem->storage_path);
        } catch (RuntimeException) {
            abort(Response::HTTP_NOT_FOUND);
        }
        abort_unless(is_file($path) && is_readable($path), Response::HTTP_NOT_FOUND);

        // Estado e cidade só existem para o Brasil; fora dele, o registro guarda apenas o país.
        $brasilId = Pais::query()->where('codigo', 'BR')->value('id');
        $doBrasil = $brasilId !== null && (string) $request->input('pais_id') === (string) $brasilId;

        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'perfil_download_id' => ['required', 'integer', Rule::exists('perfis_download', 'id')->where('ativo', true)],
            'motivo_download_id' => ['required', 'integer', Rule::exists('motivos_download', 'id')->where('ativo', true)],
            'finalidade' => ['required', 'string', 'min:10', 'max:1000'],
            'pais_id' => ['required', 'integer', Rule::exists('paises', 'id')->where('ativo', true)],
            'estado_id' => [Rule::excludeIf(! $doBrasil), 'required', 'integer', Rule::exists('estados', 'id')],
            'municipio_id' => [Rule::excludeIf(! $doBrasil), 'required', 'integer', Rule::exists('municipios', 'id')->where('estado_id', (int) $request->input('estado_id'))],
            'aceite' => ['accepted'],
        ], [
            'aceite.accepted' => 'Confirme as condições de uso para baixar a fotografia.',
            'municipio_id.exists' => 'Selecione uma cidade do estado escolhido.',
        ]);

        RegistroDownload::create([
            'item_acervo_id' => $item->id,
            'titulo_item' => $item->titulo,
            'codigo_referencia_item' => $item->codigo_referencia,
            'arquivo_id' => $imagem->id,
            'versao_arquivo' => $imagem->versao_arquivo,
            'motivo_download_id' => $dados['motivo_download_id'],
            'perfil_download_id' => $dados['perfil_download_id'],
            'pais_id' => $dados['pais_id'],
            'estado_id' => $dados['estado_id'] ?? null,
            'municipio_id' => $dados['municipio_id'] ?? null,
            'nome' => $dados['nome'],
            'email' => $dados['email'],
            'finalidade' => $dados['finalidade'],
            'termos_aceitos_em' => now(),
            'created_at' => now(),
        ]);

        $extensao = match ($imagem->mime_type) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'img',
        };
        $response = response()->download($path, "memorias-vivas-{$item->id}.{$extensao}", [
            'Content-Type' => $imagem->mime_type,
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-store');

        return $response;
    }

    /** @param Collection<int, Arquivo> $arquivos */
    private function imagemParaDownload($arquivos): Arquivo
    {
        $imagem = $arquivos->firstWhere('versao_arquivo', 'large')
            ?? $arquivos->firstWhere('versao_arquivo', 'medium')
            ?? $arquivos->firstWhere('versao_arquivo', 'thumbnail');

        abort_unless($imagem, Response::HTTP_NOT_FOUND);

        return $imagem;
    }
}
