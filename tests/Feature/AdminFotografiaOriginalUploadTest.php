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
        $arquivos = Arquivo::where('item_acervo_id', $fotografia->id)->orderBy('id')->get();
        $arquivo = $arquivos->firstWhere('versao_arquivo', 'original');
        $thumbnail = $arquivos->firstWhere('versao_arquivo', 'thumbnail');
        $medium = $arquivos->firstWhere('versao_arquivo', 'medium');
        $large = $arquivos->firstWhere('versao_arquivo', 'large');

        $this->assertCount(4, $arquivos);
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

        $this->assertSame([320, 240], [$thumbnail->width, $thumbnail->height]);
        $this->assertSame([640, 480], [$medium->width, $medium->height]);
        $this->assertSame([640, 480], [$large->width, $large->height]);

        foreach ([$thumbnail, $medium, $large] as $derivacao) {
            $this->assertNull($derivacao->nome_original);
            $this->assertSame('local', $derivacao->provider);
            $this->assertSame('image/jpeg', $derivacao->mime_type);
            $this->assertSame('imagem', $derivacao->tipo_arquivo);
            $this->assertSame(64, strlen((string) $derivacao->sha256));
            $this->assertStringStartsWith("acervo/derivados/{$fotografia->id}/", $derivacao->storage_path);
            Storage::disk('local')->assertExists($derivacao->storage_path);
        }
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

        $arquivo = Arquivo::where('item_acervo_id', $fotografia->id)
            ->where('versao_arquivo', 'original')
            ->firstOrFail();

        $this->assertSame('retrato.png', $arquivo->nome_original);
        $this->assertSame('image/png', $arquivo->mime_type);
        $this->assertSame(300, $arquivo->width);
        $this->assertSame(200, $arquivo->height);
        Storage::disk('local')->assertExists($arquivo->storage_path);
        $this->assertSame(
            ['large', 'medium', 'original', 'thumbnail'],
            Arquivo::where('item_acervo_id', $fotografia->id)->pluck('versao_arquivo')->sort()->values()->all(),
        );
    }

    public function test_original_pdf_upload_does_not_generate_optimized_image_versions(): void
    {
        Storage::fake('local');

        $this
            ->actingAs($this->usuarioInterno())
            ->post(route('admin.fotografias.store'), $this->validPayload([
                'titulo' => 'Fotografia com PDF original',
                'arquivo_original' => UploadedFile::fake()->create('documento.pdf', 128, 'application/pdf'),
            ]))
            ->assertRedirect(route('admin.fotografias.index'));

        $fotografia = ItemAcervo::where('titulo', 'Fotografia com PDF original')->firstOrFail();
        $arquivo = Arquivo::where('item_acervo_id', $fotografia->id)->sole();

        $this->assertSame('documento.pdf', $arquivo->nome_original);
        $this->assertSame('application/pdf', $arquivo->mime_type);
        $this->assertSame('documento', $arquivo->tipo_arquivo);
        $this->assertSame('original', $arquivo->versao_arquivo);
        $this->assertNull($arquivo->width);
        $this->assertNull($arquivo->height);
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

    public function test_duplicate_original_file_is_rejected_during_registration(): void
    {
        Storage::fake('local');
        $arquivoOriginal = UploadedFile::fake()->image('foto-duplicada.jpg', 320, 240)->size(128);
        $hash = hash_file('sha256', $arquivoOriginal->getRealPath());
        $fotografiaExistente = $this->fotografia(['titulo' => 'Fotografia já cadastrada']);
        $fotografiaExistente->arquivos()->create([
            'nome_original' => 'foto-duplicada.jpg',
            'provider' => 'local',
            'storage_path' => 'acervo/originais/existente.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'tipo_arquivo' => 'imagem',
            'sha256' => $hash,
            'versao_arquivo' => 'original',
            'width' => 320,
            'height' => 240,
        ]);

        $this
            ->actingAs($this->usuarioInterno())
            ->from(route('admin.fotografias.create'))
            ->post(route('admin.fotografias.store'), $this->validPayload([
                'titulo' => 'Nova tentativa duplicada',
                'arquivo_original' => $arquivoOriginal,
            ]))
            ->assertRedirect(route('admin.fotografias.create'))
            ->assertSessionHasErrors([
                'arquivo_original' => 'Este arquivo parece já estar cadastrado na fotografia "Fotografia já cadastrada" (#'.$fotografiaExistente->id.').',
            ]);

        $this->assertDatabaseMissing('item_acervos', [
            'titulo' => 'Nova tentativa duplicada',
        ]);
        $this->assertSame(1, Arquivo::where('sha256', $hash)->count());
    }

    public function test_duplicate_original_file_is_rejected_during_edit(): void
    {
        Storage::fake('local');
        $arquivoOriginal = UploadedFile::fake()->image('foto-duplicada.jpg', 320, 240)->size(128);
        $hash = hash_file('sha256', $arquivoOriginal->getRealPath());
        $fotografiaExistente = $this->fotografia(['titulo' => 'Fotografia já cadastrada']);
        $fotografiaExistente->arquivos()->create([
            'nome_original' => 'foto-duplicada.jpg',
            'provider' => 'local',
            'storage_path' => 'acervo/originais/existente.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'tipo_arquivo' => 'imagem',
            'sha256' => $hash,
            'versao_arquivo' => 'original',
            'width' => 320,
            'height' => 240,
        ]);
        $fotografiaSemArquivo = $this->fotografia(['titulo' => 'Fotografia sem arquivo']);

        $this
            ->actingAs($this->usuarioInterno())
            ->from(route('admin.fotografias.edit', $fotografiaSemArquivo))
            ->put(route('admin.fotografias.update', $fotografiaSemArquivo), $this->validPayload([
                'titulo' => 'Tentativa de edição duplicada',
                'arquivo_original' => $arquivoOriginal,
            ]))
            ->assertRedirect(route('admin.fotografias.edit', $fotografiaSemArquivo))
            ->assertSessionHasErrors([
                'arquivo_original' => 'Este arquivo parece já estar cadastrado na fotografia "Fotografia já cadastrada" (#'.$fotografiaExistente->id.').',
            ]);

        $this->assertDatabaseHas('item_acervos', [
            'id' => $fotografiaSemArquivo->id,
            'titulo' => 'Fotografia sem arquivo',
        ]);
        $this->assertSame(0, Arquivo::where('item_acervo_id', $fotografiaSemArquivo->id)->count());
        $this->assertSame(1, Arquivo::where('sha256', $hash)->count());
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
