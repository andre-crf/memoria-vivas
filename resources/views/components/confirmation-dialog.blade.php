<div x-data="confirmationDialog" @submit.window="intercept($event)">
    <dialog
        x-ref="dialog"
        role="alertdialog"
        aria-modal="true"
        aria-labelledby="confirmation-dialog-title"
        aria-describedby="confirmation-dialog-message"
        class="fixed inset-0 m-auto w-[min(32rem,calc(100%-2rem))] rounded-lg border border-stone-200 bg-white p-0 text-stone-950 shadow-2xl backdrop:bg-stone-950/60"
        @cancel.prevent="cancel()"
        @close="restoreFocus()"
        @click.self="cancel()"
    >
        <div class="p-6">
            <h2 id="confirmation-dialog-title" class="text-xl font-semibold" x-text="title"></h2>
            <p id="confirmation-dialog-message" class="mt-3 text-sm leading-6 text-stone-600" x-text="message"></p>

            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button
                    x-ref="cancel"
                    type="button"
                    class="inline-flex items-center justify-center rounded-md border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:bg-stone-100 focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2"
                    @click="cancel()"
                >
                    Cancelar
                </button>
                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-md px-4 py-2 text-sm font-semibold text-white shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                    :class="variant === 'danger' ? 'bg-red-700 hover:bg-red-800 focus:ring-red-700' : 'bg-[#173F35] hover:bg-[#0f2b24] focus:ring-[#173F35]'"
                    :disabled="submitting"
                    @click="approve()"
                    x-text="confirmLabel"
                ></button>
            </div>
        </div>
    </dialog>
</div>
