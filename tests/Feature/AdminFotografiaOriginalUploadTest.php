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

class AdminFotografiaOriginalUploadTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioInterno(string $role = 'admin'): User
    {
        return User::factory()->create([
            'role' => $role,
            'status' => 'ativo',
        ]);
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    private function fotografia(array $dados = []): ItemAcervo
    {
        return ItemAcervo::create($dados + [
            'titulo' => 'Praça central em obras',
            'tipo_item' => 'fotografia',
            'tipo_data' => 'ano',
            'ano' => 1980,
            'estado_conservacao' => 'bom',
            'status' => 'rascunho',
            'visibilidade' => Visibilidade::Privado,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $override = []): array
    {
        return $override + [
            'titulo' => 'Praça central restaurada',
            'tipo_data' => 'ano',
            'ano' => 1981,
            'estado_conservacao' => 'regular',
            'status' => 'publicado',
            'visibilidade' => Visibilidade::Publico->value,
        ];
    }

    public function test_create_form_shows_original_file_upload_field(): void
    {
        $this
            ->actingAs($this->usuarioInterno())
            ->get(route('admin.fotografias.create'))
            ->assertOk()
            ->assertSee('Arquivo original')
            ->assertSee('Formatos aceitos: .jpg, .jpeg, .png, .webp, .tif, .tiff, .pdf')
            ->assertSee('até 50 MB')
            ->assertSee('name="arquivo_original"', false)
            ->assertSee('enctype="multipart/form-data"', false);
    }

    public function test_internal_user_can_register_photograph_with_original_image(): void
    {
        Storage::fake('local');

        $this
            ->actingAs($this->usuarioInterno('operador'))
            ->post(route('admin.fotografias.store'), $this->validPayload([
                'titulo' => 'Fotografia com original',
                'arquivo_original' => UploadedFile::fake()->image('praca-central.jpg', 640, 480)->size(256),
            ]))
            ->assertRedirect(route('admin.fotografias.index'));

        $fotografia = ItemAcervo::where('titulo', 'Fotografia com original')->firstOrFail();
        $arquivo = Arquivo::where('item_acervo_id', $fotografia->id)->firstOrFail();

        $this->assertSame('praca-central.jpg', $arquivo->nome_original);
        $this->assertSame('local', $arquivo->provider);
        $this->assertSame('image/jpeg', $arquivo->mime_type);
        $this->assertSame('imagem', $arquivo->tipo_arquivo);
        $this->assertSame('original', $arquivo->versao_arquivo);
        $this->assertSame(640, $arquivo->width);
        $this->assertSame(480, $arquivo->height);
        $this->assertSame(64, strlen((string) $arquivo->sha256));
        $this->assertStringStartsWith("acervo/originais/{$fotografia->id}/", $arquivo->storage_path);
        Storage::disk('local')->assertExists($arquivo->storage_path);
    }

    public function test_internal_user_can_attach_original_file_during_edit_when_missing(): void
    {
        Storage::fake('local');
        $fotografia = $this->fotografia();

        $this
            ->actingAs($this->usuarioInterno())
            ->put(route('admin.fotografias.update', $fotografia), $this->validPayload([
                'arquivo_original' => UploadedFile::fake()->image('retrato.png', 300, 200)->size(128),
            ]))
            ->assertRedirect(route('admin.fotografias.show', $fotografia));

        $arquivo = Arquivo::where('item_acervo_id', $fotografia->id)->firstOrFail();

        $this->assertSame('retrato.png', $arquivo->nome_original);
        $this->assertSame('image/png', $arquivo->mime_type);
        $this->assertSame(300, $arquivo->width);
        $this->assertSame(200, $arquivo->height);
        Storage::disk('local')->assertExists($arquivo->storage_path);
    }

    public function test_invalid_original_file_is_rejected(): void
    {
        Storage::fake('local');

        $this
            ->actingAs($this->usuarioInterno())
            ->from(route('admin.fotografias.create'))
            ->post(route('admin.fotografias.store'), $this->validPayload([
                'arquivo_original' => UploadedFile::fake()->create('anotacoes.txt', 8, 'text/plain'),
            ]))
            ->assertRedirect(route('admin.fotografias.create'))
            ->assertSessionHasErrors('arquivo_original');

        $this->assertDatabaseMissing('item_acervos', [
            'titulo' => 'Praça central restaurada',
        ]);
        $this->assertSame(0, Arquivo::count());
    }

    public function test_original_file_size_limit_is_configurable(): void
    {
        Storage::fake('local');
        config(['acervo.uploads.original.max_kb' => 100]);

        $this
            ->actingAs($this->usuarioInterno())
            ->from(route('admin.fotografias.create'))
            ->post(route('admin.fotografias.store'), $this->validPayload([
                'arquivo_original' => UploadedFile::fake()->image('foto-grande.jpg', 640, 480)->size(101),
            ]))
            ->assertRedirect(route('admin.fotografias.create'))
            ->assertSessionHasErrors([
                'arquivo_original' => 'O arquivo original não pode passar de 100 KB.',
            ]);

        $this->assertDatabaseMissing('item_acervos', [
            'titulo' => 'Praça central restaurada',
        ]);
        $this->assertSame(0, Arquivo::count());
    }

    public function test_original_file_extension_must_be_accepted(): void
    {
        Storage::fake('local');

        $this
            ->actingAs($this->usuarioInterno())
            ->from(route('admin.fotografias.create'))
            ->post(route('admin.fotografias.store'), $this->validPayload([
                'arquivo_original' => UploadedFile::fake()->create('foto.exe', 1, 'image/jpeg'),
            ]))
            ->assertRedirect(route('admin.fotografias.create'))
            ->assertSessionHasErrors([
                'arquivo_original' => 'O arquivo original deve usar uma das extensões aceitas: jpg, jpeg, png, webp, tif, tiff, pdf.',
            ]);

        $this->assertDatabaseMissing('item_acervos', [
            'titulo' => 'Praça central restaurada',
        ]);
        $this->assertSame(0, Arquivo::count());
    }

    public function test_edit_does_not_replace_existing_original_file(): void
    {
        Storage::fake('local');
        $fotografia = $this->fotografia();
        $fotografia->arquivos()->create([
            'nome_original' => 'original.jpg',
            'provider' => 'local',
            'storage_path' => 'acervo/originais/original.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'tipo_arquivo' => 'imagem',
            'versao_arquivo' => 'original',
            'width' => 640,
            'height' => 480,
        ]);

        $this
            ->actingAs($this->usuarioInterno())
            ->from(route('admin.fotografias.edit', $fotografia))
            ->put(route('admin.fotografias.update', $fotografia), $this->validPayload([
                'arquivo_original' => UploadedFile::fake()->image('novo-original.jpg', 300, 200)->size(128),
            ]))
            ->assertRedirect(route('admin.fotografias.edit', $fotografia))
            ->assertSessionHasErrors('arquivo_original');

        $this->assertSame(1, Arquivo::where('item_acervo_id', $fotografia->id)->count());
        $this->assertDatabaseHas('arquivos', [
            'item_acervo_id' => $fotografia->id,
            'nome_original' => 'original.jpg',
        ]);
    }
}
