<?php

namespace App\Livewire\Admin;

use App\Http\Requests\Admin\StoreAssuntoRequest;
use App\Http\Requests\Admin\StoreAutorRequest;
use App\Http\Requests\Admin\StoreCategoriaRequest;
use App\Http\Requests\Admin\StorePalavraChaveRequest;
use App\Models\Assunto;
use App\Models\Autor;
use App\Models\Categoria;
use App\Models\PalavraChave;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

final class QuickCatalogCreate extends Component
{
    #[Locked]
    public string $catalog;

    /** @var list<array{id: string, label: string, description?: string}> */
    public array $options = [];

    /** @var list<string> */
    public array $selected = [];

    public string $selectedSingle = '';

    /** @var array<string, mixed> */
    public array $form = [];

    public string $status = '';

    public function mount(string $catalog, iterable $initialOptions, mixed $initialSelected = null): void
    {
        $this->catalog = $catalog;
        $this->definition();
        $this->options = collect($initialOptions)
            ->map(fn (mixed $option): array => $this->optionFrom($option))
            ->values()
            ->all();
        $this->form = $this->emptyForm();

        if ($this->isAuthor()) {
            $this->selectedSingle = filled($initialSelected) ? (string) $initialSelected : '';

            return;
        }

        $this->selected = collect($initialSelected ?? [])
            ->map(fn (mixed $id): string => (string) $id)
            ->unique()
            ->values()
            ->all();
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

        array_unshift($this->options, $option);

        if ($this->isAuthor()) {
            $this->selectedSingle = $option['id'];
        } else {
            $this->selected = collect([...$this->selected, $option['id']])
                ->unique()
                ->values()
                ->all();
        }

        $this->form = $this->emptyForm();
        $this->status = 'Criado e selecionado.';
        $this->resetValidation();
        $this->dispatch('quick-catalog-created', catalog: $this->catalog);
    }

    public function updatedForm(): void
    {
        $this->status = '';
    }

    public function render(): View
    {
        return view('livewire.admin.quick-catalog-create', [
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
     *     search_placeholder?: string,
     *     empty_message?: string,
     *     article: string,
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
                'description' => 'Selecione um autor existente ou cadastre um sem abandonar o formulário.',
                'article' => 'novo autor',
                'fields' => [
                    ['name' => 'nome', 'label' => 'Nome'],
                    ['name' => 'tipo', 'label' => 'Tipo', 'type' => 'select', 'options' => ['pessoa' => 'Pessoa', 'instituicao' => 'Instituição']],
                ],
            ],
            'categoria' => [
                'model' => Categoria::class,
                'request' => StoreCategoriaRequest::class,
                'input' => 'categoria_ids',
                'label' => 'Categorias',
                'description' => 'Classificação geral do item.',
                'search_placeholder' => 'Buscar categoria...',
                'empty_message' => 'Nenhuma categoria cadastrada.',
                'article' => 'categoria',
                'fields' => [['name' => 'titulo', 'label' => 'Título']],
            ],
            'assunto' => [
                'model' => Assunto::class,
                'request' => StoreAssuntoRequest::class,
                'input' => 'assunto_ids',
                'label' => 'Assuntos',
                'description' => 'Temas retratados na fotografia.',
                'search_placeholder' => 'Buscar assunto...',
                'empty_message' => 'Nenhum assunto cadastrado.',
                'article' => 'assunto',
                'fields' => [['name' => 'titulo', 'label' => 'Título']],
            ],
            'palavra-chave' => [
                'model' => PalavraChave::class,
                'request' => StorePalavraChaveRequest::class,
                'input' => 'palavra_chave_ids',
                'label' => 'Palavras-chave',
                'description' => 'Termos específicos para facilitar buscas.',
                'search_placeholder' => 'Buscar palavra-chave...',
                'empty_message' => 'Nenhuma palavra-chave cadastrada.',
                'article' => 'palavra-chave',
                'fields' => [['name' => 'termo', 'label' => 'Termo']],
            ],
            default => throw new \InvalidArgumentException("Catálogo rápido [{$this->catalog}] não suportado."),
        };
    }

    /** @return array<string, mixed> */
    private function emptyForm(): array
    {
        return match ($this->catalog) {
            'autor' => ['nome' => '', 'tipo' => 'pessoa', 'observacao' => null],
            'categoria', 'assunto' => ['titulo' => '', 'descricao' => null],
            'palavra-chave' => ['termo' => ''],
        };
    }

    /** @return array{id: string, label: string, description?: string} */
    private function optionFrom(mixed $record): array
    {
        $value = static fn (mixed $source, string $key): mixed => is_array($source)
            ? $source[$key]
            : $source->{$key};

        return match ($this->catalog) {
            'autor' => [
                'id' => (string) $value($record, 'id'),
                'label' => (string) $value($record, 'nome'),
                'description' => $value($record, 'tipo') === 'instituicao' ? 'Instituição' : 'Pessoa',
            ],
            'categoria', 'assunto' => [
                'id' => (string) $value($record, 'id'),
                'label' => (string) $value($record, 'titulo'),
            ],
            'palavra-chave' => [
                'id' => (string) $value($record, 'id'),
                'label' => (string) $value($record, 'termo'),
            ],
        };
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
