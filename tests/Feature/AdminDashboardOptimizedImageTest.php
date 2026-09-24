<?php

namespace Tests\Feature;

use App\Enums\Visibilidade;
use App\Models\ItemAcervo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardOptimizedImageTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioInterno(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'status' => 'ativo',
        ]);
    }

    public function test_recent_items_use_thumbnail_version_when_available(): void
    {
        $fotografia = ItemAcervo::create([
            'titulo' => 'Praça central no dashboard',
            'tipo_item' => 'fotografia',
            'tipo_data' => 'ano',
            'ano' => 1980,
            'status' => 'rascunho',
            'visibilidade' => Visibilidade::Privado,
        ]);
        $original = $fotografia->arquivos()->create([
            'nome_original' => 'original.jpg',
            'provider' => 'local',
            'storage_path' => 'acervo/originais/original.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 2048,
            'tipo_arquivo' => 'imagem',
            'versao_arquivo' => 'original',
            'width' => 1200,
            'height' => 800,
        ]);
        $thumbnail = $fotografia->arquivos()->create([
            'nome_original' => null,
            'provider' => 'local',
            'storage_path' => 'acervo/derivados/thumbnail.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 512,
            'tipo_arquivo' => 'imagem',
            'versao_arquivo' => 'thumbnail',
            'width' => 320,
            'height' => 213,
        ]);

        $this
            ->actingAs($this->usuarioInterno())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.arquivos.show', $thumbnail), false)
            ->assertDontSee(route('admin.arquivos.show', $original), false)
            ->assertSee('Miniatura de Praça central no dashboard', false);
    }
}
