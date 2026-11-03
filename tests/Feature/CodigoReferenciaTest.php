<?php

namespace Tests\Feature;

use App\Auditing\Enums\AuditAction;
use App\Auditing\Enums\AuditEntity;
use App\Enums\Visibilidade;
use App\Models\AuditEvent;
use App\Models\ItemAcervo;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Código de referência: identificação manual, opcional e única informada pela instituição.
 */
class CodigoReferenciaTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_saves_the_code_and_stores_empty_values_as_null(): void
    {
        $this->cadastrar(['titulo' => 'Com código', 'codigo_referencia' => 'BR UMU MV-FOT-0001'])->assertSessionHasNoErrors();
        $this->cadastrar(['titulo' => 'Sem código'])->assertSessionHasNoErrors();
        $this->cadastrar(['titulo' => 'Código vazio', 'codigo_referencia' => ''])->assertSessionHasNoErrors();
        $this->cadastrar(['titulo' => 'Só espaços', 'codigo_referencia' => '   '])->assertSessionHasNoErrors();

        $this->assertSame('BR UMU MV-FOT-0001', ItemAcervo::firstWhere('titulo', 'Com código')->codigo_referencia);

        foreach (['Sem código', 'Código vazio', 'Só espaços'] as $titulo) {
            $this->assertNull(ItemAcervo::firstWhere('titulo', $titulo)->codigo_referencia, $titulo);
        }
    }

    public function test_code_is_limited_to_one_hundred_characters(): void
    {
        $this->cadastrar(['titulo' => 'No limite', 'codigo_referencia' => str_repeat('A', 100)])->assertSessionHasNoErrors();

        $this->cadastrar(['titulo' => 'Acima do limite', 'codigo_referencia' => str_repeat('A', 101)])
            ->assertSessionHasErrors(['codigo_referencia' => 'O código de referência pode ter no máximo 100 caracteres.']);
        $this->assertNull(ItemAcervo::firstWhere('titulo', 'Acima do limite'));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function codigosValidos(): array
    {
        return [
            'espaços e hífens' => ['BR UMU MV-FOT-0001'],
            'barras' => ['MV/1965/03'],
            'pontos' => ['CX12.P4'],
            'underscore, dois-pontos e #' => ['ACERVO_1:SÉRIE#3'],
            'só números' => ['000123'],
            'começando por #' => ['#45'],
            'acentos' => ['Coleção-Ação-7'],
        ];
    }

    #[DataProvider('codigosValidos')]
    public function test_common_separators_letters_and_numbers_are_accepted(string $codigo): void
    {
        $this->cadastrar(['titulo' => 'Válido', 'codigo_referencia' => $codigo])->assertSessionHasNoErrors();

        $this->assertSame($codigo, ItemAcervo::firstWhere('titulo', 'Válido')->codigo_referencia);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function codigosInvalidos(): array
    {
        return [
            'arroba' => ['MV@01'],
            'asterisco' => ['MV*01'],
            'quebra de linha' => ["MV\n01"],
            'tabulação' => ["MV\t01"],
            'só separadores' => ['--/--'],
            'aspas' => ['MV"01'],
        ];
    }

    #[DataProvider('codigosInvalidos')]
    public function test_unexpected_characters_are_rejected(string $codigo): void
    {
        $this->cadastrar(['titulo' => 'Inválido', 'codigo_referencia' => $codigo])
            ->assertSessionHasErrors(['codigo_referencia' => 'Use no código de referência apenas letras, números, espaços e os separadores . - / _ : #.']);

        $this->assertNull(ItemAcervo::firstWhere('titulo', 'Inválido'));
    }

    public function test_duplicates_are_rejected_regardless_of_letter_case(): void
    {
        $existente = $this->fotografia(['titulo' => 'Praça central', 'codigo_referencia' => 'MV-FOT-001']);

        foreach (['MV-FOT-001', 'mv-fot-001', 'Mv-Fot-001'] as $codigo) {
            $this->cadastrar(['titulo' => "Duplicada {$codigo}", 'codigo_referencia' => $codigo])
                ->assertSessionHasErrors(['codigo_referencia' => "Este código de referência já pertence à fotografia \"Praça central\" (#{$existente->id})."]);
        }

        $this->assertSame(1, ItemAcervo::count());
    }

    public function test_edit_keeps_corrects_changes_and_removes_the_code(): void
    {
        $fotografia = $this->fotografia(['titulo' => 'Coreto', 'codigo_referencia' => 'MV-001']);
        $outra = $this->fotografia(['titulo' => 'Avenida', 'codigo_referencia' => 'MV-002']);

        // Salvar sem mudar e corrigir só maiúsculas do próprio código não conflita consigo mesmo.
        $this->editar($fotografia, ['codigo_referencia' => 'MV-001'])->assertSessionHasNoErrors();
        $this->editar($fotografia, ['codigo_referencia' => 'mv-001'])->assertSessionHasNoErrors();
        $this->assertSame('mv-001', $fotografia->fresh()->codigo_referencia);

        $this->editar($fotografia, ['codigo_referencia' => 'MV-002'])
            ->assertSessionHasErrors(['codigo_referencia' => "Este código de referência já pertence à fotografia \"Avenida\" (#{$outra->id})."]);
        $this->assertSame('mv-001', $fotografia->fresh()->codigo_referencia);

        $this->editar($fotografia, ['codigo_referencia' => 'MV-003'])->assertSessionHasNoErrors();
        $this->assertSame('MV-003', $fotografia->fresh()->codigo_referencia);

        $this->editar($fotografia, ['codigo_referencia' => ''])->assertSessionHasNoErrors();
        $this->assertNull($fotografia->fresh()->codigo_referencia);
    }

    public function test_trashed_photographs_keep_their_codes_reserved(): void
    {
        $excluida = $this->fotografia(['titulo' => 'Na lixeira', 'codigo_referencia' => 'MV-LIX-01']);
        $excluida->delete();

        $this->cadastrar(['titulo' => 'Nova', 'codigo_referencia' => 'mv-lix-01'])
            ->assertSessionHasErrors(['codigo_referencia' => "Este código de referência já pertence à fotografia \"Na lixeira\" (#{$excluida->id}), que está na lixeira."]);

        $excluida->restore();
        $this->assertSame('MV-LIX-01', $excluida->fresh()->codigo_referencia);
    }

    public function test_code_can_be_reused_after_permanent_deletion(): void
    {
        $this->fotografia(['titulo' => 'Removida', 'codigo_referencia' => 'MV-DEF-01'])->forceDelete();

        $this->cadastrar(['titulo' => 'Sucessora', 'codigo_referencia' => 'MV-DEF-01'])->assertSessionHasNoErrors();
        $this->assertSame('MV-DEF-01', ItemAcervo::firstWhere('titulo', 'Sucessora')->codigo_referencia);
    }

    public function test_database_keeps_the_code_unique_and_allows_many_photos_without_code(): void
    {
        $this->fotografia(['titulo' => 'Primeira sem código']);
        $this->fotografia(['titulo' => 'Segunda sem código']);
        $this->fotografia(['titulo' => 'Com código', 'codigo_referencia' => 'MV-UNI-01']);

        $this->assertSame(2, ItemAcervo::whereNull('codigo_referencia')->count());
        $this->expectException(UniqueConstraintViolationException::class);
        $this->fotografia(['titulo' => 'Repetida', 'codigo_referencia' => 'MV-UNI-01']);
    }

    public function test_concurrent_use_of_the_same_code_returns_a_validation_error(): void
    {
        // Simula outra requisição gravando o mesmo código entre a validação e a gravação.
        $simulado = false;
        ItemAcervo::creating(function () use (&$simulado): void {
            if ($simulado) {
                return;
            }

            $simulado = true;
            DB::table('item_acervos')->insert(['titulo' => 'Gravada antes', 'codigo_referencia' => 'MV-CON-01']);
        });

        $this->cadastrar(['titulo' => 'Concorrente', 'codigo_referencia' => 'MV-CON-01'])
            ->assertSessionHasErrors(['codigo_referencia' => 'Este código de referência acabou de ser usado em outra fotografia. Informe outro código.']);

        $this->assertNull(ItemAcervo::firstWhere('titulo', 'Concorrente'));
        $this->assertSame(0, AuditEvent::count());
    }

    public function test_admin_search_finds_photographs_by_code(): void
    {
        $this->fotografia(['titulo' => 'Coreto', 'codigo_referencia' => 'BR UMU MV-FOT-0042']);
        $this->fotografia(['titulo' => 'Avenida', 'codigo_referencia' => 'BR UMU MV-FOT-0099']);

        $titulos = collect($this->actingAs($this->admin())
            ->get(route('admin.fotografias.index', ['q' => 'fot-0042']))
            ->assertOk()
            ->viewData('fotografias')
            ->items())->pluck('titulo')->all();

        $this->assertSame(['Coreto'], $titulos);
    }

    public function test_form_and_admin_details_show_the_code(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.fotografias.create'))
            ->assertOk()
            ->assertSeeInOrder(['Título', 'Código de referência', 'Legenda/descrição'])
            ->assertSee('Identificação utilizada pela instituição. Não corresponde ao ID do sistema nem à localização física.')
            ->assertSee('name="codigo_referencia"', false)
            ->assertSee('maxlength="100"', false);

        $comCodigo = $this->fotografia(['titulo' => 'Com código', 'codigo_referencia' => 'MV-DET-01']);
        $semCodigo = $this->fotografia(['titulo' => 'Sem código']);

        $this->actingAs($admin)->get(route('admin.fotografias.edit', $comCodigo))
            ->assertOk()
            ->assertSee('value="MV-DET-01"', false);
        $this->actingAs($admin)->get(route('admin.fotografias.show', $comCodigo))
            ->assertOk()
            ->assertSeeInOrder(['Código de referência', 'MV-DET-01']);
        $this->actingAs($admin)->get(route('admin.fotografias.show', $semCodigo))
            ->assertOk()
            ->assertSeeInOrder(['Código de referência', 'Não informado']);
    }

    public function test_public_details_show_the_code_only_when_filled_and_never_the_physical_location(): void
    {
        $comCodigo = $this->publicada(['titulo' => 'Pública com código', 'codigo_referencia' => 'MV-PUB-01', 'localizacao_fisica' => 'Armário 3, caixa 12']);
        $semCodigo = $this->publicada(['titulo' => 'Pública sem código']);

        $this->get(route('public.fotografias.show', $comCodigo->id))
            ->assertOk()
            ->assertSeeInOrder(['Código de referência', 'MV-PUB-01'])
            ->assertDontSee('Armário 3, caixa 12');
        $this->get(route('public.fotografias.show', $semCodigo->id))
            ->assertOk()
            ->assertDontSee('Código de referência');
    }

    public function test_audit_records_the_code_on_creation_and_on_changes_as_free_text(): void
    {
        $admin = $this->admin();
        $this->cadastrar(['titulo' => 'Auditada', 'codigo_referencia' => '1965-03-10T10:00:00-03:00'])->assertSessionHasNoErrors();
        $fotografia = ItemAcervo::firstWhere('titulo', 'Auditada');
        $criacao = AuditEvent::where('subject_type', AuditEntity::ItemAcervo)->where('action', AuditAction::Created)->sole();

        $this->assertSame('1965-03-10T10:00:00-03:00', $criacao->new_values['codigo_referencia']);

        $this->editar($fotografia, ['titulo' => 'Auditada', 'codigo_referencia' => 'MV-AUD-01'])->assertSessionHasNoErrors();
        $alteracao = AuditEvent::where('subject_type', AuditEntity::ItemAcervo)->where('action', AuditAction::Updated)->sole();

        $this->assertSame('1965-03-10T10:00:00-03:00', $alteracao->old_values['codigo_referencia']);
        $this->assertSame('MV-AUD-01', $alteracao->new_values['codigo_referencia']);

        $this->editar($fotografia, ['titulo' => 'Auditada', 'codigo_referencia' => ''])->assertSessionHasNoErrors();
        $remocao = AuditEvent::where('subject_type', AuditEntity::ItemAcervo)->where('action', AuditAction::Updated)->latest('id')->first();

        $this->assertSame('MV-AUD-01', $remocao->old_values['codigo_referencia']);
        $this->assertNull($remocao->new_values['codigo_referencia']);

        // Um código no formato de data e hora ISO é exibido como digitado, não convertido em data.
        $this->actingAs($admin)->get(route('admin.auditoria.show', $criacao))
            ->assertOk()
            ->assertSee('Código de referência')
            ->assertSee('1965-03-10T10:00:00-03:00')
            ->assertDontSee('10/03/1965');
    }

    /** @param array<string, mixed> $dados */
    private function cadastrar(array $dados): TestResponse
    {
        return $this->actingAs($this->admin())->post(route('admin.fotografias.store'), $dados + $this->camposObrigatorios());
    }

    /** @param array<string, mixed> $dados */
    private function editar(ItemAcervo $fotografia, array $dados): TestResponse
    {
        return $this->actingAs($this->admin())->put(route('admin.fotografias.update', $fotografia), $dados + ['titulo' => $fotografia->titulo] + $this->camposObrigatorios());
    }

    /** @return array<string, mixed> */
    private function camposObrigatorios(): array
    {
        return [
            'tipo_data' => 'ano',
            'ano' => 1980,
            'estado_conservacao' => 'bom',
            'status' => 'rascunho',
            'visibilidade' => Visibilidade::Privado->value,
        ];
    }

    /** @param array<string, mixed> $dados */
    private function fotografia(array $dados): ItemAcervo
    {
        return ItemAcervo::create($dados + [
            'tipo_item' => 'fotografia',
            'tipo_data' => 'desconhecida',
            'status' => 'rascunho',
            'visibilidade' => Visibilidade::Privado,
        ]);
    }

    /** @param array<string, mixed> $dados */
    private function publicada(array $dados): ItemAcervo
    {
        $fotografia = $this->fotografia($dados + ['status' => 'publicado', 'visibilidade' => Visibilidade::Publico]);
        $fotografia->arquivos()->create([
            'provider' => 'local', 'storage_path' => "acervo/derivados/{$fotografia->id}/large.webp", 'mime_type' => 'image/webp',
            'file_size' => 100, 'tipo_arquivo' => 'imagem', 'versao_arquivo' => 'large', 'width' => 800, 'height' => 600,
        ]);

        return $fotografia;
    }

    private function admin(): User
    {
        return User::query()->firstWhere('email', 'admin@memorias.test')
            ?? User::factory()->create(['email' => 'admin@memorias.test', 'role' => 'admin', 'status' => 'ativo']);
    }
}
