<div x-data="confirmationDialog" @submit.window="intercept($event)">
    <dialog
        x-ref="dialog"
        role="alertdialog"
        aria-modal="true"
        aria-labelledby="confirmation-dialog-title"
        aria-describedby="confirmation-dialog-message"
        class="admin-dialog w-[min(32rem,calc(100%-2rem))]"
        @cancel.prevent="cancel()"
        @close="restoreFocus()"
        @click.self="cancel()"
    >
        <div class="px-5 pt-5 pb-4">
            <h2 id="confirmation-dialog-title" class="text-section font-semibold" x-text="title"></h2>
            <p id="confirmation-dialog-message" class="mt-2 text-sm leading-6 text-fg-muted" x-text="message"></p>
        </div>

        <div class="flex flex-col-reverse gap-2 border-t border-line px-5 py-4 sm:flex-row sm:justify-end">
            <x-catalog.button variant="secondary" x-ref="cancel" x-on:click="cancel()">Cancelar</x-catalog.button>
            {{-- A variante vem do formulário (data-confirm-variant): vermelho cheio para destruir, primário para o resto. --}}
            <x-catalog.button variant="danger-solid" x-show="variant === 'danger'" x-bind:disabled="submitting" x-on:click="approve()" x-text="confirmLabel" />
            <x-catalog.button x-show="variant !== 'danger'" x-bind:disabled="submitting" x-on:click="approve()" x-text="confirmLabel" />
        </div>
    </dialog>
</div>
