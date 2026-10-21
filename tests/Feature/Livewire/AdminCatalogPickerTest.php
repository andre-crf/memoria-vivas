<?php

namespace Tests\Feature\Livewire;

use App\Enums\Visibilidade;
use App\Livewire\Admin\CatalogPicker;
use App\Models\Assunto;
use App\Models\Autor;
use App\Models\Categoria;
use App\Models\ItemAcervo;
use App\Models\PalavraChave;
use App\Models\Pessoa;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class AdminCatalogPickerTest extends TestCase
{
    use RefreshDatabase;

    public function test_author_is_created_listed_and_selected(): void
    {
        Livewire::actingAs($this->internalUser());

        $component = Livewire::test(CatalogPicker::class, $this->parameters('autor'))
            ->set('form.nome', 'Arquivo Municipal')
            ->set('form.tipo', 'instituicao')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Arquivo Municipal')
            ->assertDispatched('toast', message: 'Autor “Arquivo Municipal” cadastrado e selecionado.');

        $autor = Autor::query()->where('nome', 'Arquivo Municipal')->firstOrFail();

        $component->assertSet('selectedSingle', (string) $autor->id);
    }

    public function test_category_is_created_listed_and_selected(): void
    {
        $this->assertMultipleCatalogCreation(
            catalog: 'categoria',
            field: 'titulo',
            value: 'Paisagem urbana',
            model: Categoria::class,
        );
    }

    public function test_subject_is_created_listed_and_selected(): void
    {
        $this->assertMultipleCatalogCreation(
            catalog: 'assunto',
            field: 'titulo',
            value: 'Patrimônio ferroviário',
            model: Assunto::class,
        );
    }

    public function test_keyword_is_created_listed_and_selected(): void
    {
        $this->assertMultipleCatalogCreation(
            catalog: 'palavra-chave',
            field: 'termo',
            value: 'estação',
            model: PalavraChave::class,
        );
    }

    public function test_invalid_data_uses_the_existing_request_rules(): void
    {
        Livewire::actingAs($this->internalUser());

        Livewire::test(CatalogPicker::class, $this->parameters('categoria'))
            ->set('form.titulo', '')
            ->call('save')
            ->assertHasErrors(['form.titulo' => 'required']);

        $existing = Categoria::create(['titulo' => 'Duplicada']);

        Livewire::test(CatalogPicker::class, $this->parameters('categoria'))
            ->set('form.titulo', 'Duplicada')
            ->call('save')
            ->assertHasErrors(['form.titulo' => 'unique']);

        $this->assertDatabaseCount('categorias', 1);
    }

    public function test_creation_is_denied_when_the_policy_rejects_the_user(): void
    {
        Livewire::actingAs(User::factory()->create([
            'role' => 'operador',
            'status' => 'inativo',
        ]));

        Livewire::test(CatalogPicker::class, $this->parameters('categoria'))
            ->set('form.titulo', 'Não autorizada')
            ->call('save')
            ->assertForbidden();

        $this->assertDatabaseMissing('categorias', ['titulo' => 'Não autorizada']);
    }

    public function test_previously_selected_records_are_preserved_after_creation(): void
    {
        $existing = Categoria::create(['titulo' => 'Existente']);
        Livewire::actingAs($this->internalUser());

        $component = Livewire::test(
            CatalogPicker::class,
            $this->parameters('categoria', [$existing->id]),
        )
            ->set('form.titulo', 'Nova categoria')
            ->call('save');

        $created = Categoria::query()->where('titulo', 'Nova categoria')->firstOrFail();

        $component->assertSet('selected', [(string) $existing->id, (string) $created->id]);
        $this->assertSame(2, substr_count($component->html(), 'name="categoria_ids&#91;&#93;"'));

        $document = new DOMDocument;
        @$document->loadHTML($component->html());
        $inputs = (new DOMXPath($document))->query('//input[@name="categoria_ids[]"]');

        $this->assertNotFalse($inputs);
        $this->assertCount(2, $inputs);
    }

    public function test_multiple_picker_instances_keep_independent_state(): void
    {
        $existingSubject = Assunto::create(['titulo' => 'Assunto existente']);
        Livewire::actingAs($this->internalUser());

        $category = Livewire::test(CatalogPicker::class, $this->parameters('categoria'))
            ->set('form.titulo', 'Categoria independente')
            ->call('save');
        $subject = Livewire::test(CatalogPicker::class, $this->parameters('assunto'));

        $category
            ->assertSee('Categoria independente')
            ->assertDispatched('quick-catalog-created', catalog: 'categoria');
        $subject
            ->assertSet('selected', [])
            ->assertDontSee('Categoria independente')
            ->assertSee('Assunto existente')
            ->assertDontSee('name="assunto_ids&#91;&#93;"', false);
    }

    public function test_loading_state_and_cleared_fields_prevent_duplicate_submission(): void
    {
        Livewire::actingAs($this->internalUser());

        $component = Livewire::test(CatalogPicker::class, $this->parameters('categoria'))
            ->assertSee('wire:loading.attr="disabled"', false)
            ->assertSee('wire:target="save"', false)
            ->set('form.titulo', 'Categoria única')
            ->call('save')
            ->call('save')
            ->assertHasErrors(['form.titulo' => 'required']);

        $this->assertDatabaseCount('categorias', 1);
        $component->assertSet('form.titulo', '');
    }

    public function test_created_values_are_accepted_by_the_traditional_photograph_post(): void
    {
        $user = $this->internalUser();
        Livewire::actingAs($user);

        $author = Livewire::test(CatalogPicker::class, $this->parameters('autor'))
            ->set('form.nome', 'Autor criado no formulário')
            ->set('form.tipo', 'pessoa')
            ->call('save');
        $category = $this->createdMultipleComponent('categoria', 'titulo', 'Categoria criada no formulário');
        $subject = $this->createdMultipleComponent('assunto', 'titulo', 'Assunto criado no formulário');
        $keyword = $this->createdMultipleComponent('palavra-chave', 'termo', 'termo criado no formulário');

        $this->actingAs($user)
            ->post(route('admin.fotografias.store'), [
                'titulo' => 'Fotografia com cadastros rápidos',
                'tipo_data' => 'desconhecida',
                'estado_conservacao' => 'desconhecido',
                'status' => 'rascunho',
                'visibilidade' => Visibilidade::Privado->value,
                'autor_id' => $author->get('selectedSingle'),
                'categoria_ids' => $category->get('selected'),
                'assunto_ids' => $subject->get('selected'),
                'palavra_chave_ids' => $keyword->get('selected'),
            ])
            ->assertRedirect(route('admin.fotografias.index'));

        $photograph = ItemAcervo::query()->where('titulo', 'Fotografia com cadastros rápidos')->firstOrFail();

        $this->assertSame((int) $author->get('selectedSingle'), $photograph->autor_id);
        $this->assertSame($category->get('selected'), $photograph->categorias()->pluck('categorias.id')->map(fn (int $id): string => (string) $id)->all());
        $this->assertSame($subject->get('selected'), $photograph->assuntos()->pluck('assuntos.id')->map(fn (int $id): string => (string) $id)->all());
        $this->assertSame($keyword->get('selected'), $photograph->palavrasChave()->pluck('palavras_chave.id')->map(fn (int $id): string => (string) $id)->all());
    }

    public function test_search_is_limited_ordered_and_excludes_selected_items(): void
    {
        Livewire::actingAs($this->internalUser());
        $categorias = collect(range(1, 12))->map(fn (int $i) => Categoria::create(['titulo' => sprintf('Praça %02d', $i)]));
        Categoria::create(['titulo' => 'Rua principal']);

        $component = Livewire::test(CatalogPicker::class, $this->parameters('categoria', [$categorias[0]->id]))
            ->set('search', 'praça');

        $labels = collect($component->get('results'))->pluck('label')->all();

        $this->assertCount(8, $labels);
        $this->assertSame('Praça 02', $labels[0]);
        $this->assertNotContains('Praça 01', $labels);
        $this->assertNotContains('Rua principal', $labels);
    }

    public function test_search_treats_like_wildcards_as_text(): void
    {
        Livewire::actingAs($this->internalUser());
        Categoria::create(['titulo' => '100% preservado']);
        Categoria::create(['titulo' => '1000 registros']);

        $component = Livewire::test(CatalogPicker::class, $this->parameters('categoria'))
            ->set('search', '100%');

        $this->assertSame(['100% preservado'], collect($component->get('results'))->pluck('label')->all());
    }

    public function test_search_returns_nothing_when_the_policy_rejects_the_user(): void
    {
        Categoria::create(['titulo' => 'Restrita']);
        Livewire::actingAs(User::factory()->create(['role' => 'operador', 'status' => 'inativo']));

        $component = Livewire::test(CatalogPicker::class, $this->parameters('categoria'));

        $this->assertSame([], $component->get('results'));
    }

    public function test_items_are_selected_and_removed_as_chips_with_hidden_inputs(): void
    {
        Livewire::actingAs($this->internalUser());
        $praca = Categoria::create(['titulo' => 'Praças']);
        $ruas = Categoria::create(['titulo' => 'Ruas']);

        $component = Livewire::test(CatalogPicker::class, $this->parameters('categoria'))
            ->set('search', 'pra')
            ->call('select', (string) $praca->id)
            ->call('select', (string) $ruas->id)
            ->assertSet('search', '')
            ->assertSet('selected', [(string) $praca->id, (string) $ruas->id])
            ->assertSee('Remover Praças')
            ->call('remove', (string) $praca->id)
            ->assertSet('selected', [(string) $ruas->id]);

        $this->assertSame(1, substr_count($component->html(), 'name="categoria_ids&#91;&#93;"'));
    }

    public function test_author_selection_is_single_and_replaces_the_previous_one(): void
    {
        Livewire::actingAs($this->internalUser());
        $primeiro = Autor::create(['nome' => 'Foto Estúdio', 'tipo' => 'instituicao']);
        $segundo = Autor::create(['nome' => 'João Fotógrafo', 'tipo' => 'pessoa']);

        Livewire::test(CatalogPicker::class, $this->parameters('autor', $primeiro->id))
            ->assertSet('selectedSingle', (string) $primeiro->id)
            ->call('select', (string) $segundo->id)
            ->assertSet('selectedSingle', (string) $segundo->id)
            ->assertSet('labels', [(string) $segundo->id => 'João Fotógrafo'])
            ->assertSee('name="autor_id" value="'.$segundo->id.'"', false);
    }

    public function test_unknown_initial_ids_are_discarded(): void
    {
        Livewire::actingAs($this->internalUser());
        $existing = Categoria::create(['titulo' => 'Existente']);

        Livewire::test(CatalogPicker::class, $this->parameters('categoria', [$existing->id, 999]))
            ->assertSet('selected', [(string) $existing->id]);
    }

    public function test_start_create_prefills_the_form_with_the_search_term(): void
    {
        Livewire::actingAs($this->internalUser());

        Livewire::test(CatalogPicker::class, $this->parameters('palavra-chave'))
            ->set('search', '  estação ferroviária ')
            ->call('startCreate')
            ->assertSet('form.termo', 'estação ferroviária')
            ->assertDispatched('open-catalog-create', catalog: 'palavra-chave');
    }

    public function test_person_is_created_and_selected(): void
    {
        $this->assertMultipleCatalogCreation(
            catalog: 'pessoa',
            field: 'nome',
            value: 'Maria Aparecida',
            model: Pessoa::class,
        );
    }

    /** @param class-string<Model> $model */
    private function assertMultipleCatalogCreation(string $catalog, string $field, string $value, string $model): void
    {
        Livewire::actingAs($this->internalUser());

        $component = Livewire::test(CatalogPicker::class, $this->parameters($catalog))
            ->set("form.{$field}", $value)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee($value)
            ->assertDispatched('quick-catalog-created', catalog: $catalog)
            ->assertDispatched('toast');

        $record = $model::query()->where($field, $value)->firstOrFail();

        $component->assertSet('selected', [(string) $record->getKey()]);
    }

    private function createdMultipleComponent(string $catalog, string $field, string $value): Testable
    {
        return Livewire::test(CatalogPicker::class, $this->parameters($catalog))
            ->set("form.{$field}", $value)
            ->call('save');
    }

    /** @return array<string, mixed> */
    private function parameters(string $catalog, mixed $selected = null): array
    {
        return [
            'catalog' => $catalog,
            'initialSelected' => $selected,
        ];
    }

    private function internalUser(): User
    {
        return User::factory()->create([
            'role' => 'operador',
            'status' => 'ativo',
        ]);
    }
}
