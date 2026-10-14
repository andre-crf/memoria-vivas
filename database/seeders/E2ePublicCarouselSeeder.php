<?php

namespace Database\Seeders;

use App\Enums\Visibilidade;
use App\Models\Arquivo;
use App\Models\ItemAcervo;
use Illuminate\Database\Seeder;

class E2ePublicCarouselSeeder extends Seeder
{
    public function run(): void
    {
        foreach (range(1, 3) as $indice) {
            $fotografia = ItemAcervo::create([
                'titulo' => "Fotografia pública E2E {$indice}",
                'tipo_item' => 'fotografia',
                'tipo_data' => 'desconhecida',
                'status' => 'publicado',
                'visibilidade' => Visibilidade::Publico,
            ]);

            Arquivo::create([
                'item_acervo_id' => $fotografia->id,
                'provider' => 'local',
                'storage_path' => "e2e/carrossel-{$indice}.png",
                'mime_type' => 'image/png',
                'file_size' => 68,
                'tipo_arquivo' => 'imagem',
                'versao_arquivo' => 'medium',
                'width' => 1,
                'height' => 1,
            ]);
        }
    }
}
