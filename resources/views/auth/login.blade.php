<x-layouts.app title="Entrar | Memórias Vivas">
    <main class="grid min-h-screen lg:grid-cols-[minmax(0,0.92fr)_minmax(480px,0.68fr)]">
        <section class="hidden bg-[#173F35] text-stone-50 lg:flex lg:flex-col lg:justify-between">
            <div class="px-12 pt-12">
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#D6C17A]">Memórias Vivas</p>
            </div>

            <div class="px-12 pb-14">
                <div class="max-w-2xl">
                    <p class="mb-5 inline-flex rounded-full border border-stone-100/20 px-3 py-1 text-xs uppercase tracking-[0.16em] text-stone-200">
                        Área administrativa
                    </p>
                    <h1 class="text-5xl font-semibold leading-tight text-white">
                        Organização segura do acervo histórico de Umuarama.
                    </h1>
                    <p class="mt-6 max-w-xl text-base leading-7 text-stone-200">
                        Acesse para catalogar fotografias, revisar metadados e preparar os registros que serão disponibilizados para a comunidade.
                    </p>
                </div>
            </div>
        </section>

        <section class="flex min-h-screen items-center justify-center px-6 py-10 sm:px-10">
            <div class="w-full max-w-md">
                <div class="mb-8 lg:hidden">
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#6B5E2E]">Memórias Vivas</p>
                    <h1 class="mt-4 text-3xl font-semibold text-stone-950">Área administrativa</h1>
                </div>

                <div class="rounded-lg border border-stone-200 bg-white p-6 shadow-sm sm:p-8">
                    <div class="mb-6">
                        <h2 class="text-2xl font-semibold text-stone-950">Entrar</h2>
                        <p class="mt-2 text-sm leading-6 text-stone-600">
                            Use seu e-mail institucional ou cadastrado no sistema.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
                        @csrf

                        <div>
                            <label for="email" class="block text-sm font-medium text-stone-800">E-mail</label>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                value="{{ old('email') }}"
                                autocomplete="email"
                                required
                                autofocus
                                class="mt-2 block w-full rounded-md border border-stone-300 bg-white px-3 py-2.5 text-sm text-stone-950 outline-none transition focus:border-[#173F35] focus:ring-2 focus:ring-[#173F35]/20"
                            >
                            @error('email')
                                <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-stone-800">Senha</label>
                            <input
                                id="password"
                                name="password"
                                type="password"
                                autocomplete="current-password"
                                required
                                class="mt-2 block w-full rounded-md border border-stone-300 bg-white px-3 py-2.5 text-sm text-stone-950 outline-none transition focus:border-[#173F35] focus:ring-2 focus:ring-[#173F35]/20"
                            >
                            @error('password')
                                <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                            @enderror
                        </div>

                        <label class="flex items-center gap-3 text-sm text-stone-700">
                            <input
                                name="remember"
                                type="checkbox"
                                value="1"
                                class="h-4 w-4 rounded border-stone-300 text-[#173F35] focus:ring-[#173F35]"
                            >
                            Manter conectado
                        </label>

                        <button
                            type="submit"
                            class="flex w-full items-center justify-center rounded-md bg-[#173F35] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#0F2F27] focus:outline-none focus:ring-2 focus:ring-[#173F35] focus:ring-offset-2"
                        >
                            Entrar no admin
                        </button>
                    </form>
                </div>
            </div>
        </section>
    </main>
</x-layouts.app>
