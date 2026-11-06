<x-layouts.app title="Entrar | Memórias Vivas">
    <main class="grid min-h-screen lg:grid-cols-[minmax(0,0.92fr)_minmax(480px,0.68fr)]">
        <section class="hidden bg-institutional text-white lg:flex lg:flex-col lg:justify-between">
            <div class="px-12 pt-12">
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-heritage-300">Memórias Vivas</p>
            </div>

            <div class="px-12 pb-14">
                <div class="max-w-2xl">
                    <p class="mb-5 inline-flex rounded-full border border-white/20 px-3 py-1 text-xs uppercase tracking-[0.16em] text-institutional-muted">
                        Área administrativa
                    </p>
                    <h1 class="text-5xl font-semibold leading-tight text-white">
                        Organização segura do acervo histórico de Umuarama.
                    </h1>
                    <p class="mt-6 max-w-xl text-base leading-7 text-institutional-muted">
                        Acesse para catalogar fotografias, revisar metadados e preparar os registros que serão disponibilizados para a comunidade.
                    </p>
                </div>
            </div>
        </section>

        <section class="flex min-h-screen items-center justify-center px-6 py-10 sm:px-10">
            <div class="w-full max-w-md">
                <div class="mb-8 lg:hidden">
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-heritage-700">Memórias Vivas</p>
                    <h1 class="mt-4 text-3xl font-semibold text-fg">Área administrativa</h1>
                </div>

                <div class="rounded-lg border border-line bg-white p-6 shadow-card sm:p-8">
                    <div class="mb-6">
                        <h2 class="text-2xl font-semibold text-fg">Entrar</h2>
                        <p class="mt-2 text-sm leading-6 text-fg-muted">
                            Use seu e-mail institucional ou cadastrado no sistema.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('login.store') }}" class="grid gap-5">
                        @csrf

                        <div class="grid gap-1.5">
                            <x-catalog.label for="email" text="E-mail">
                                <x-catalog.control id="email" name="email" type="email" :value="old('email')" autocomplete="email" required autofocus error="email" />
                            </x-catalog.label>
                            <x-catalog.error field="email" as="p" />
                        </div>

                        <div class="grid gap-1.5">
                            <x-catalog.label for="password" text="Senha">
                                <x-catalog.control id="password" name="password" type="password" autocomplete="current-password" required error="password" />
                            </x-catalog.label>
                            <x-catalog.error field="password" as="p" />
                        </div>

                        <label class="flex items-center gap-3 text-sm text-ink-700">
                            <input
                                name="remember"
                                type="checkbox"
                                value="1"
                                class="size-4 flex-none cursor-pointer accent-institutional focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-institutional"
                            >
                            Manter conectado
                        </label>

                        <x-catalog.button type="submit" class="w-full">Entrar no admin</x-catalog.button>
                    </form>
                </div>
            </div>
        </section>
    </main>
</x-layouts.app>
