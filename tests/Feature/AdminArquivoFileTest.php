<?php

namespace Tests\Feature;

use App\Enums\Visibilidade;
use App\Models\Arquivo;
use App\Models\ItemAcervo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminArquivoFileTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioInterno(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'status' => 'ativo',
        ]);
    }

    private function arquivo(string $storagePath = 'acervo/derivados/thumb.jpg'): Arquivo
    {
        $fotografia = ItemAcervo::create([
            'titulo' => 'Praça central',
            'tipo_item' => 'fotografia',
            'tipo_data' => 'ano',
            'ano' => 1980,
            'status' => 'rascunho',
            'visibilidade' => Visibilidade::Privado,
        ]);

        return $fotografia->arquivos()->create([
            'nome_original' => null,
            'provider' => 'local',
            'storage_path' => $storagePath,
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'tipo_arquivo' => 'imagem',
            'sha256' => str_repeat('a', 64),
            'versao_arquivo' => 'thumbnail',
            'width' => 320,
            'height' => 240,
        ]);
    }

    public function test_guest_is_redirected_from_admin_file_route(): void
    {
        $arquivo = $this->arquivo();

        $this
            ->get(route('admin.arquivos.show', $arquivo))
            ->assertRedirect('/login');
    }

    public function test_internal_user_can_view_local_file(): void
    {
        Storage::fake('local');
        $uploadedFile = UploadedFile::fake()->image('thumb.jpg', 320, 240);
        Storage::disk('local')->put('acervo/derivados/thumb.jpg', file_get_contents($uploadedFile->getRealPath()));
        $arquivo = $this->arquivo();

        $this
            ->actingAs($this->usuarioInterno())
            ->get(route('admin.arquivos.show', $arquivo))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');
    }

    public function test_missing_local_file_returns_not_found(): void
    {
        Storage::fake('local');
        $arquivo = $this->arquivo('acervo/derivados/inexistente.jpg');

        $this
            ->actingAs($this->usuarioInterno())
            ->get(route('admin.arquivos.show', $arquivo))
            ->assertNotFound();
    }
}
