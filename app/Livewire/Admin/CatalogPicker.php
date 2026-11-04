<?php

namespace App\Livewire\Admin;

use App\Http\Requests\Admin\StoreAssuntoRequest;
use App\Http\Requests\Admin\StoreAutorRequest;
use App\Http\Requests\Admin\StoreCategoriaRequest;
use App\Http\Requests\Admin\StorePalavraChaveRequest;
use App\Http\Requests\Admin\StorePessoaRequest;
use App\Models\Assunto;
use App\Models\Autor;
use App\Models\Categoria;
use App\Models\PalavraChave;
use App\Models\Pessoa;
use App\Queries\Concerns\PesquisaTextual;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Seletor de catálogo do formulário de fotografia: busca no servidor, mostra os
 * selecionados como chips e permite cadastrar um item novo sem sair do formulário.
 */
final class CatalogPicker extends Component
{
    use PesquisaTextual;

    private const RESULT_LIMIT = 8;

    #[Locked]
    public string $catalog;

    /** @var list<string> */
    public array $selected = [];

    public string $selectedSingle = '';

    /** @var array<string, string> Rótulos dos itens selecionados, por id. */
    public array $labels = [];

    public string $search = '';

    /** @var array<string, mixed> */
    public array $form = [];

    public function mount(string $catalog, mixed $initialSelected = null): void
    {
        $this->catalog = $catalog;
        $this->definition();
        $this->form = $this->emptyForm();

        $ids = $this->isAuthor()
            ? (filled($initialSelected) ? [(string) $initialSelected] : [])
            : collect($initialSelected ?? [])->map(fn (mixed $id): string => (string) $id)->unique()->values()->all();

        // Ids inexistentes (ex.: old() de um envio com erro) são descartados.
        $this->labels = $this->labelsFor($ids);
        $existing = array_values(array_filter($ids, fn (string $id): bool => isset($this->labels[$id])));

        if ($this->isAuthor()) {
            $this->selectedSingle = $existing[0] ?? '';

            return;
        }

        $this->selected = $existing;
    }

    /** @return list<array{id: string, label: string, description?: string}> */
    #[Computed]
    public function results(): array
    {
        $definition = $this->definition();

        // As requisições do Livewire não passam pelo middleware das rotas do admin.
        if (! Gate::allows('viewAny', $definition['model'])) {
            return [];
        }

        $column = $definition['label_column'];
        $term = trim($this->search);

        return $definition['model']::query()
            ->when($term !== '', fn ($query) => $query->whereRaw(
                "{$column} LIKE ? ESCAPE '!'",
                [$this->padraoDeBusca($term)],
            ))
            ->whereNotIn('id', $this->selectedIds())
            ->orderBy($column)
            ->limit(self::RESULT_LIMIT)
            ->get()
            ->map(fn (Model $record): array => $this->optionFrom($record))
            ->all();
    }

    public function select(string $id): void
    {
        $label = $this->labelsFor([$id])[$id] ?? null;

        if ($label === null) {
            return;
        }

        if ($this->isAuthor()) {
            $this->selectedSingle = $id;
            $this->labels = [$id => $label];
        } else {
            $this->selected = collect([...$this->selected, $id])->unique()->values()->all();
            $this->labels[$id] = $label;
        }

        $this->search = '';
    }

    public function remove(string $id): void
    {
        if ($this->isAuthor()) {
            $this->selectedSingle = $this->selectedSingle === $id ? '' : $this->selectedSingle;
        } else {
            $this->selected = array_values(array_diff($this->selected, [$id]));
        }

        unset($this->labels[$id]);
    }

    /** Abre o cadastro rápido já preenchido com o termo buscado. */
    public function startCreate(): void
    {
        $this->form[$this->definition()['create_field']] = trim($this->search);
        $this->resetValidation();
        $this->dispatch('open-catalog-create', catalog: $this->catalog);
    }

    public function save(): void
    {
        $definition = $this->definition();
        Gate::authorize('create', $definition['model']);

        $requestClass = $definition['request'];
        /** @var FormRequest $request */
        $request = new $requestClass;
        $validated = $this->validate(
            $this->prefixed($request->rules()),
            $this->prefixedMessages($request->messages()),
            $this->prefixedAttributes($request->attributes()),
        );

        /** @var Model $record */
        $record = $definition['model']::query()->create($validated['form']);
        $option = $this->optionFrom($record);

        $this->select($option['id']);
        $this->form = $this->emptyForm();
        $this->resetValidation();
        $this->dispatch('quick-catalog-created', catalog: $this->catalog);
        $this->dispatch('toast', message: sprintf($definition['created_message'], $option['label']));
    }

    public function render(): View
    {
        return view('livewire.admin.catalog-picker', [
            'definition' => $this->definition(),
            'isAuthor' => $this->isAuthor(),
        ]);
    }

    /**
     * @return array{
     *     model: class-string<Model>,
     *     request: class-string<FormRequest>,
     *     input: string,
     *     label: string,
     *     description: string,
     *     label_column: string,
     *     create_field: string,
     *     create_label: string,
     *     created_message: string,
     *     search_placeholder: string,
     *     empty_message: string,
     *     fields: list<array{name: string, label: string, type?: string, options?: array<string, string>}>
     * }
     */
    private function definition(): array
    {
        return match ($this->catalog) {
            'autor' => [
                'model' => Autor::class,
                'request' => StoreAutorRequest::class,
                'input' => 'autor_id',
                'label' => 'Autor',
                'description' => 'Quem fez o registro fotográfico.',
                'label_column' => 'nome',
                'create_field' => 'nome',
                'create_label' => 'Cadastrar novo autor',
                'created_message' => 'Autor “%s” cadastrado e selecionado.',
                'search_placeholder' => 'Buscar autor...',
                'empty_message' => 'Nenhum autor cadastrado.',
                'fields' => [
                    ['name' => 'nome', 'label' => 'Nome'],
                    ['name' => 'tipo', 'label' => 'Tipo', 'type' => 'select', 'options' => ['pessoa' => 'Pessoa', 'instituicao' => 'Instituição']],
                ],
            ],
            'pessoa' => [
                'model' => Pessoa::class,
                'request' => StorePessoaRequest::class,
                'input' => 'pessoa_ids',
                'label' => 'Pessoas',
                'description' => 'Quem aparece na imagem.',
                'label_column' => 'nome',
                'create_field' => 'nome',
                'create_label' => 'Cadastrar pessoa',
                'created_message' => 'Pessoa “%s” cadastrada e selecionada.',
                'search_placeholder' => 'Buscar pessoa...',
                'empty_message' => 'Nenhuma pessoa cadastrada.',
                'fields' => [['name' => 'nome', 'label' => 'Nome']],
            ],
            'categoria' => [
                'model' => Categoria::class,
                'request' => StoreCategoriaRequest::class,
                'input' => 'categoria_ids',
                'label' => 'Categorias',
                'description' => 'Classificação geral do item.',
                'label_column' => 'titulo',
                'create_field' => 'titulo',
                'create_label' => 'Criar categoria',
                'created_message' => 'Categoria “%s” criada e selecionada.',
                'search_placeholder' => 'Buscar categoria...',
                'empty_message' => 'Nenhuma categoria cadastrada.',
                'fields' => [['name' => 'titulo', 'label' => 'Título']],
            ],
            'assunto' => [
                'model' => Assunto::class,
                'request' => StoreAssuntoRequest::class,
                'input' => 'assunto_ids',
                'label' => 'Assuntos',
                'description' => 'Temas retratados na fotografia.',
                'label_column' => 'titulo',
                'create_field' => 'titulo',
                'create_label' => 'Criar assunto',
                'created_message' => 'Assunto “%s” criado e selecionado.',
                'search_placeholder' => 'Buscar assunto...',
                'empty_message' => 'Nenhum assunto cadastrado.',
                'fields' => [['name' => 'titulo', 'label' => 'Título']],
            ],
            'palavra-chave' => [
                'model' => PalavraChave::class,
                'request' => StorePalavraChaveRequest::class,
                'input' => 'palavra_chave_ids',
                'label' => 'Palavras-chave',
                'description' => 'Termos específicos para facilitar buscas.',
                'label_column' => 'termo',
                'create_field' => 'termo',
                'create_label' => 'Criar palavra-chave',
                'created_message' => 'Palavra-chave “%s” criada e selecionada.',
                'search_placeholder' => 'Buscar palavra-chave...',
                'empty_message' => 'Nenhuma palavra-chave cadastrada.',
                'fields' => [['name' => 'termo', 'label' => 'Termo']],
            ],
            default => throw new \InvalidArgumentException("Catálogo [{$this->catalog}] não suportado."),
        };
    }

    /** @return array<string, mixed> */
    private function emptyForm(): array
    {
        return match ($this->catalog) {
            'autor' => ['nome' => '', 'tipo' => 'pessoa', 'observacao' => null],
            'pessoa' => ['nome' => '', 'observacao' => null],
            'categoria', 'assunto' => ['titulo' => '', 'descricao' => null],
            'palavra-chave' => ['termo' => ''],
        };
    }

    /** @return list<string> */
    private function selectedIds(): array
    {
        return $this->isAuthor()
            ? array_filter([$this->selectedSingle])
            : $this->selected;
    }

    /**
     * @param  list<string>  $ids
     * @return array<string, string>
     */
    private function labelsFor(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $definition = $this->definition();
        $labels = $definition['model']::query()
            ->whereKey($ids)
            ->pluck($definition['label_column'], 'id')
            ->mapWithKeys(fn (string $label, int|string $id): array => [(string) $id => $label])
            ->all();

        // Mantém a ordem de seleção.
        return array_filter(array_replace(array_fill_keys($ids, null), $labels));
    }

    /** @return array{id: string, label: string, description?: string} */
    private function optionFrom(Model $record): array
    {
        $option = [
            'id' => (string) $record->getKey(),
            'label' => (string) $record->{$this->definition()['label_column']},
        ];

        if ($this->isAuthor()) {
            $option['description'] = $record->tipo === 'instituicao' ? 'Instituição' : 'Pessoa';
        }

        return $option;
    }

    private function isAuthor(): bool
    {
        return $this->catalog === 'autor';
    }

    /**
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    private function prefixed(array $rules): array
    {
        return collect($rules)
            ->mapWithKeys(fn (mixed $rule, string $field): array => ["form.{$field}" => $rule])
            ->all();
    }

    /**
     * @param  array<string, string>  $messages
     * @return array<string, string>
     */
    private function prefixedMessages(array $messages): array
    {
        return collect($messages)
            ->mapWithKeys(function (string $message, string $key): array {
                [$field, $rule] = array_pad(explode('.', $key, 2), 2, null);

                return ['form.'.$field.($rule ? ".{$rule}" : '') => $message];
            })
            ->all();
    }

    /**
     * @param  array<string, string>  $attributes
     * @return array<string, string>
     */
    private function prefixedAttributes(array $attributes): array
    {
        return collect($attributes)
            ->mapWithKeys(fn (string $attribute, string $field): array => ["form.{$field}" => $attribute])
            ->all();
    }
}
