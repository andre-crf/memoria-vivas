<x-layouts.app title="Entrar | Memórias Vivas">
    <main class="grid min-h-screen lg:grid-cols-[minmax(0,0.92fr)_minmax(480px,0.68fr)]">
        <section class="relative isolate hidden overflow-hidden bg-institutional text-white lg:flex lg:flex-col lg:justify-between">
            <img src="{{ asset('images/login-acervo-historico.jpg') }}" alt="" width="842" height="562" class="absolute inset-0 size-full object-cover object-center" aria-hidden="true">
            <div class="absolute inset-0 bg-linear-to-t from-institutional-strong via-institutional/75 to-institutional/35" aria-hidden="true"></div>

            <div class="relative px-12 pt-12">
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-heritage-300">Memórias Vivas</p>
            </div>

            <div class="relative px-12 pb-14">
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

        <section class="relative isolate flex min-h-screen items-center justify-center overflow-hidden bg-paper px-6 py-10 sm:px-10">
            <img src="{{ asset('images/login-album-historico.jpg') }}" alt="" width="1008" height="617" class="absolute inset-0 hidden size-full object-cover object-center opacity-25 grayscale lg:block" aria-hidden="true">
            <div class="absolute inset-0 hidden bg-linear-to-b from-paper/70 via-paper/85 to-paper/75 lg:block" aria-hidden="true"></div>

            <div class="relative w-full max-w-md">
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

                        <x-catalog.password id="password" label="Senha" autocomplete="current-password" required :marcador="false" />

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
