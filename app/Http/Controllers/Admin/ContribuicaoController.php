<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contribuicao;
use App\Models\ContribuicaoArquivo;
use App\Support\OrdenacaoListagem;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ContribuicaoController extends Controller
{
    public function index(Request $request): View
    {
        $ordenacao = OrdenacaoListagem::fromRequest($request, [
            'assunto' => 'titulo',
            'colaborador' => 'nome',
            'arquivos' => 'arquivos_count',
            'recebida' => 'created_at',
        ], 'recebida', 'desc', comecamDecrescentes: ['arquivos', 'recebida']);

        return view('admin.contribuicoes.index', [
            'contribuicoes' => $ordenacao->aplicar(Contribuicao::withCount('arquivos'))
                ->paginate(20)
                ->appends($ordenacao->parametros()),
            'ordenacao' => $ordenacao,
        ]);
    }

    public function show(Contribuicao $contribuicao): View
    {
        return view('admin.contribuicoes.show', [
            'contribuicao' => $contribuicao->load('arquivos'),
        ]);
    }

    public function download(Contribuicao $contribuicao, ContribuicaoArquivo $arquivo): BinaryFileResponse
    {
        abort_unless($arquivo->contribuicao_id === $contribuicao->id, 404);

        $caminho = Storage::disk('local')->path($arquivo->caminho);
        abort_unless(is_file($caminho), 404);

        return response()->download($caminho, $arquivo->nome_original, [
            'Content-Type' => $arquivo->mime_type,
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
