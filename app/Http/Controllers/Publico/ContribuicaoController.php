<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use App\Models\Contribuicao;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use Throwable;

class ContribuicaoController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'titulo' => ['required', 'string', 'max:180'],
            'historia' => ['nullable', 'string', 'max:3000'],
            'local' => ['nullable', 'string', 'max:180'],
            'periodo' => ['nullable', 'string', 'max:100'],
            'fotos' => ['required', 'array', 'min:1', 'max:5'],
            'fotos.*' => ['required', File::types(['jpg', 'jpeg', 'png', 'webp'])->max('10mb')],
            'autorizacao' => ['accepted'],
        ], [
            'fotos.required' => 'Selecione pelo menos uma fotografia.',
            'fotos.max' => 'Envie no máximo cinco fotografias por vez.',
            'fotos.*.mimes' => 'Envie imagens JPG, PNG ou WebP.',
            'fotos.*.max' => 'Cada fotografia pode ter até 10 MB.',
            'autorizacao.accepted' => 'Confirme que você tem permissão para compartilhar as fotografias.',
        ], [
            'nome' => 'nome',
            'email' => 'e-mail',
            'titulo' => 'título',
            'fotos' => 'fotografias',
        ]);

        $caminhos = [];

        try {
            DB::transaction(function () use ($dados, $request, &$caminhos): void {
                $contribuicao = Contribuicao::create([
                    ...collect($dados)->except('fotos')->all(),
                    'autorizacao' => true,
                ]);

                foreach ($request->file('fotos') as $foto) {
                    $caminho = $foto->store('contribuicoes/'.$contribuicao->id, 'local');

                    if ($caminho === false) {
                        throw new \RuntimeException('Não foi possível armazenar a fotografia.');
                    }

                    $caminhos[] = $caminho;
                    $contribuicao->arquivos()->create([
                        'nome_original' => $foto->getClientOriginalName(),
                        'caminho' => $caminho,
                        'mime_type' => $foto->getMimeType(),
                        'tamanho' => $foto->getSize(),
                    ]);
                }
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($caminhos);

            report($exception);

            return back()->withInput($request->except('fotos'))
                ->withErrors(['fotos' => 'Não foi possível salvar as fotografias. Tente novamente.']);
        }

        return redirect()->route('public.colaborar')->with('contribuicao_enviada', true);
    }
}
