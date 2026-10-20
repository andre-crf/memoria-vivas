<?php

namespace Database\Seeders;

use App\Enums\Visibilidade;
use App\Models\Assunto;
use App\Models\Autor;
use App\Models\Categoria;
use App\Models\Contribuicao;
use App\Models\ItemAcervo;
use App\Models\PalavraChave;
use App\Models\Pessoa;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Dados fixos para as capturas de tela de tests/Browser/visual.spec.js.
 * A fotografia fica em rascunho para não alterar o carrossel público.
 */
class E2eVisualSeeder extends Seeder
{
    private const PNG_1X1 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

    public function run(): void
    {
        // Sem o arquivo, a imagem falha e o fallback aparece ou não conforme o Alpine inicia antes
        // ou depois do erro, o que deixa as capturas instáveis.
        foreach (range(1, 3) as $indice) {
            Storage::disk('local')->put("e2e/carrossel-{$indice}.png", base64_decode(self::PNG_1X1));
        }

        $categoria = Categoria::create(['titulo' => 'Praças e logradouros', 'descricao' => 'Espaços públicos da cidade.']);
        Categoria::create(['titulo' => 'Eventos cívicos', 'descricao' => 'Desfiles, inaugurações e solenidades.']);

        $assunto = Assunto::create(['titulo' => 'Urbanização', 'descricao' => 'Crescimento e transformação da cidade.']);
        $palavraChave = PalavraChave::create(['termo' => 'praça central']);
        $pessoa = Pessoa::create(['nome' => 'Maria da Silva', 'observacao' => 'Moradora pioneira.']);
        $autor = Autor::create(['nome' => 'Foto Estúdio Umuarama', 'tipo' => 'instituicao', 'observacao' => 'Estúdio fotográfico local.']);

        $fotografia = ItemAcervo::create([
            'tipo_item' => 'fotografia',
            'titulo' => 'Praça Arthur Thomas em 1968',
            'legenda' => 'Vista da praça central durante a inauguração do coreto.',
            'tipo_data' => 'decada',
            'decada' => 1960,
            'local_atual' => 'Praça Arthur Thomas',
            'evento' => 'Inauguração do coreto',
            'estado_conservacao' => 'bom',
            'status' => 'rascunho',
            'visibilidade' => Visibilidade::Privado,
            'autor_id' => $autor->id,
        ]);

        $fotografia->categorias()->attach($categoria);
        $fotografia->assuntos()->attach($assunto);
        $fotografia->palavrasChave()->attach($palavraChave);
        $fotografia->pessoas()->attach($pessoa);

        Contribuicao::create([
            'nome' => 'João Pereira',
            'email' => 'joao@example.test',
            'titulo' => 'Fotos da família na avenida Paraná',
            'historia' => 'Meu avô registrou a avenida nos anos 1970, antes do asfalto.',
            'local' => 'Avenida Paraná',
            'periodo' => 'Década de 1970',
            'autorizacao' => true,
        ]);
    }
}
