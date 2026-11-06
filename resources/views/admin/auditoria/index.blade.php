<x-layouts.admin title="Auditoria | Memórias Vivas">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <x-admin.page-header
            class="mb-6"
            eyebrow="Administração"
            title="Auditoria do sistema"
            description="Consulte o histórico imutável das operações realizadas no sistema."
        />

        <livewire:admin.audit-event-list />
    </div>
</x-layouts.admin>
