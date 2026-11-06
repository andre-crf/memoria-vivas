<x-layouts.admin title="Contribuições | Memórias Vivas">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <x-admin.page-header
            class="mb-6"
            eyebrow="Acervo"
            title="Contribuições recebidas"
            description="Fotografias enviadas pelo público. Os arquivos são privados e ainda não fazem parte do catálogo."
        />

        <x-admin.table table-class="min-w-[52rem]" :paginator="$contribuicoes" :vazia="$contribuicoes->isEmpty()">
            <x-slot:vazio>
                <x-ui.empty-state icon="inbox" title="Nenhuma contribuição recebida ainda" description="Quando o público enviar fotografias pelo site, elas aparecerão nesta listagem." />
            </x-slot:vazio>

            <thead>
                <tr>
                    <x-admin.coluna-ordenavel rotulo="Assunto" chave="assunto" :ordenacao="$ordenacao" :action="route('admin.contribuicoes.index')" :parametros="[]" />
                    <x-admin.coluna-ordenavel rotulo="Colaborador" chave="colaborador" :ordenacao="$ordenacao" :action="route('admin.contribuicoes.index')" :parametros="[]" />
                    <x-admin.coluna-ordenavel rotulo="Arquivos" chave="arquivos" :ordenacao="$ordenacao" :action="route('admin.contribuicoes.index')" :parametros="[]" />
                    <x-admin.coluna-ordenavel rotulo="Recebida em" chave="recebida" :ordenacao="$ordenacao" :action="route('admin.contribuicoes.index')" :parametros="[]" class="whitespace-nowrap" />
                </tr>
            </thead>
            <tbody>
                @foreach ($contribuicoes as $contribuicao)
                    <tr id="contribuicao-{{ $contribuicao->id }}" class="relative">
                        <td class="min-w-56 max-w-md font-semibold text-fg"><x-admin.link-linha :href="route('admin.contribuicoes.show', $contribuicao)">{{ $contribuicao->titulo }}</x-admin.link-linha></td>
                        <td class="text-ink-700">{{ $contribuicao->nome }}</td>
                        <td class="whitespace-nowrap tabular-nums text-ink-700">{{ $contribuicao->arquivos_count }}</td>
                        <td class="whitespace-nowrap text-ink-700"><x-data-hora :valor="$contribuicao->created_at" formato="curto" /></td>
                    </tr>
                @endforeach
            </tbody>
        </x-admin.table>
    </div>
</x-layouts.admin>
