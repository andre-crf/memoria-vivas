<x-layouts.admin title="Auditoria | Memórias Vivas">
    <div class="mx-auto max-w-7xl px-6 py-8">
        <section class="mb-6">
            <p class="text-sm font-medium text-[#6B5E2E]">Administração</p>
            <h1 class="mt-2 text-3xl font-semibold text-stone-950">Auditoria do sistema</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-stone-600">
                Consulte o histórico imutável das operações realizadas no sistema.
            </p>
        </section>

        <livewire:admin.audit-event-list />
    </div>
</x-layouts.admin>
