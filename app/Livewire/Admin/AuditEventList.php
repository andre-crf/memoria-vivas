<?php

namespace App\Livewire\Admin;

use App\Auditing\AuditEventFilterRules;
use App\Auditing\AuditEventQuery;
use App\Auditing\Enums\AuditAction;
use App\Auditing\Enums\AuditEntity;
use App\Models\AuditEvent;
use App\Models\User;
use App\Support\DataExibicao;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

final class AuditEventList extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $data_inicio = '';

    #[Url(except: '')]
    public string $data_fim = '';

    #[Url(except: '')]
    public string $responsavel = '';

    #[Url(except: '')]
    public string $acao = '';

    #[Url(except: '')]
    public string $entidade = '';

    #[Url(except: '')]
    public string $entidade_id = '';

    #[Url(except: '')]
    public string $fuso = '';

    public function boot(): void
    {
        Gate::authorize('viewAudit', User::class);
    }

    public function mount(DataExibicao $datas): void
    {
        if ($this->fuso === '') {
            $this->fuso = $datas->fuso();
        }
    }

    public function hydrate(DataExibicao $datas): void
    {
        $this->fuso = $datas->fuso();
    }

    public function updated(string $property): void
    {
        if (! in_array($property, AuditEventFilterRules::FILTERS, true)) {
            return;
        }

        $this->validateFilters();
        $this->resetPage();
    }

    public function applyFilters(): void
    {
        $this->validateFilters();
        $this->resetPage();
    }

    public function clearFilters(DataExibicao $datas): void
    {
        foreach (AuditEventFilterRules::FILTERS as $filter) {
            $this->{$filter} = '';
        }

        $this->fuso = $datas->fuso();
        $this->resetValidation();
        $this->resetPage();
    }

    public function render(AuditEventQuery $events, DataExibicao $datas): View
    {
        $filters = $this->filters();
        $datas = $datas->comFuso($this->fuso);

        return view('livewire.admin.audit-event-list', [
            'eventos' => $events->forFilters($filters, $datas)->paginate(25),
            'hasAnyEvents' => AuditEvent::query()->exists(),
            'usuarios' => User::query()->orderBy('nome')->get(['id', 'nome', 'status']),
            'acoes' => AuditAction::cases(),
            'entidades' => AuditEntity::cases(),
            'navigationQuery' => $this->navigationQuery($datas),
            'fusoExibicao' => $datas->fuso(),
        ]);
    }

    /** @return array<string, string> */
    private function filters(): array
    {
        return collect(AuditEventFilterRules::FILTERS)
            ->mapWithKeys(fn (string $filter): array => [$filter => $this->{$filter}])
            ->all();
    }

    /** @return array<string, string|int> */
    private function navigationQuery(DataExibicao $datas): array
    {
        $query = collect($this->filters())
            ->put('fuso', $datas->fuso())
            ->reject(fn (string $value): bool => $value === '')
            ->all();

        if ($this->getPage() > 1) {
            $query['page'] = $this->getPage();
        }

        return $query;
    }

    private function validateFilters(): void
    {
        $rules = app(AuditEventFilterRules::class);

        $this->validate(
            $rules->rules($this->filters()),
            $rules->messages(),
            $rules->attributes(),
        );
    }
}
