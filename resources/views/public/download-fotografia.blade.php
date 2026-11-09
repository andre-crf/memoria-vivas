<x-layouts.public :title="'Baixar '.$fotografia->titulo.' | Memórias Vivas'">
    @php
        $imagemUrl = route('public.imagens.show', $imagem);
        $localidade = [
            'brasilId' => $paises->firstWhere('codigo', 'BR')?->id,
            'urlMunicipios' => route('public.localidades.municipios', ['estado' => '__ESTADO__']),
            'pais' => old('pais_id', ''),
            'estado' => old('estado_id', ''),
            'municipio' => old('municipio_id', ''),
            'municipios' => $municipios->map(fn ($municipio) => ['id' => $municipio->id, 'nome' => $municipio->nome])->values(),
        ];
    @endphp

    <section class="bg-white py-12 md:py-18" aria-labelledby="download-title">
        <x-public.container>
            <x-public.back-link :href="route('public.fotografias.show', $fotografia)">Voltar à fotografia</x-public.back-link>
            <div class="mt-8 grid gap-10 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)] lg:gap-16">
                <div>
                    <x-public.eyebrow>Acesso ao acervo</x-public.eyebrow>
                    <x-public.heading as="h1" id="download-title" class="mt-3 max-w-xl text-[length:clamp(2rem,4vw,3.3rem)] leading-[1.12] font-semibold">Baixar fotografia</x-public.heading>
                    <p class="mt-4 max-w-lg text-[0.98rem] leading-[1.7] text-ink-600">Preencha os dados de consulta para receber uma cópia digital de {{ $fotografia->titulo }}. O pedido e a finalidade de uso ficam registrados no acervo.</p>
                    <figure class="mt-7 max-w-md border border-mist-300 bg-mist-100 p-3">
                        <img src="{{ $imagemUrl }}" alt="{{ $fotografia->titulo }}" class="max-h-64 w-full object-contain">
                        <figcaption class="mt-3 text-[0.83rem] leading-[1.5] text-ink-600">{{ $fotografia->titulo }} · Registro #{{ $fotografia->id }}</figcaption>
                    </figure>
                    <p class="mt-5 max-w-lg text-[0.85rem] leading-[1.6] text-ink-600">Arquivo disponibilizado: versão {{ strtolower(\App\Models\Arquivo::rotuloVersao($imagem->versao_arquivo)) }}. A matriz original permanece preservada no acervo.</p>
                </div>

                <div class="self-start border-t border-mist-400 pt-6 lg:pt-0 lg:border-t-0">
                    @if ($errors->any())
                        <x-ui.alert tone="danger" title="Confira os campos indicados antes de continuar." class="mb-6" />
                    @endif
                    <form method="POST" action="{{ route('public.fotografias.download.store', $fotografia) }}" class="grid gap-6">
                        @csrf
                        <p class="text-[0.82rem] text-ink-600"><span class="public-required" aria-hidden="true">*</span> Campo obrigatório</p>
                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="download-name" class="public-label mb-2 block">Nome completo <span class="public-required" aria-hidden="true">*</span></label>
                                <input id="download-name" name="nome" value="{{ old('nome') }}" type="text" autocomplete="name" maxlength="120" required class="public-control" @error('nome') aria-invalid="true" aria-describedby="download-name-error" @enderror>
                                @error('nome') <p id="download-name-error" class="public-field-error mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="download-email" class="public-label mb-2 block">E-mail <span class="public-required" aria-hidden="true">*</span></label>
                                <input id="download-email" name="email" value="{{ old('email') }}" type="email" autocomplete="email" maxlength="255" required class="public-control" @error('email') aria-invalid="true" aria-describedby="download-email-error" @enderror>
                                @error('email') <p id="download-email-error" class="public-field-error mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="download-profile" class="public-label mb-2 block">Seu perfil <span class="public-required" aria-hidden="true">*</span></label>
                                <select id="download-profile" name="perfil_download_id" required class="public-control" @error('perfil_download_id') aria-invalid="true" aria-describedby="download-profile-error" @enderror>
                                    <option value="">Selecione</option>
                                    @foreach ($perfis as $perfil)
                                        <option value="{{ $perfil->id }}" @selected(old('perfil_download_id') == $perfil->id)>{{ $perfil->titulo }}</option>
                                    @endforeach
                                </select>
                                @error('perfil_download_id') <p id="download-profile-error" class="public-field-error mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="download-reason" class="public-label mb-2 block">Motivo da consulta <span class="public-required" aria-hidden="true">*</span></label>
                                <select id="download-reason" name="motivo_download_id" required class="public-control" @error('motivo_download_id') aria-invalid="true" aria-describedby="download-reason-error" @enderror>
                                    <option value="">Selecione</option>
                                    @foreach ($motivos as $motivo)
                                        <option value="{{ $motivo->id }}" @selected(old('motivo_download_id') == $motivo->id)>{{ $motivo->titulo }}</option>
                                    @endforeach
                                </select>
                                @error('motivo_download_id') <p id="download-reason-error" class="public-field-error mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <fieldset class="grid gap-5 sm:grid-cols-2" x-data="localidadeDownload(@js($localidade))" aria-describedby="download-location-hint">
                            <legend class="public-label mb-2">De onde você acessa o acervo?</legend>
                            <p id="download-location-hint" class="text-[0.82rem] leading-[1.5] text-ink-600 sm:col-span-2">Usada só para as estatísticas de acesso. Para o Brasil, informe também o estado e a cidade.</p>
                            <div class="sm:col-span-2">
                                <label for="download-country" class="public-label mb-2 block">País <span class="public-required" aria-hidden="true">*</span></label>
                                <select id="download-country" name="pais_id" required x-model="pais" class="public-control" @error('pais_id') aria-invalid="true" aria-describedby="download-country-error" @enderror>
                                    <option value="">Selecione</option>
                                    @foreach ($paises as $pais)
                                        <option value="{{ $pais->id }}" @selected((string) old('pais_id') === (string) $pais->id)>{{ $pais->nome }}</option>
                                    @endforeach
                                </select>
                                @error('pais_id') <p id="download-country-error" class="public-field-error mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div x-show="brasil" x-cloak>
                                <label for="download-state" class="public-label mb-2 block">Estado <span class="public-required" aria-hidden="true">*</span></label>
                                <select id="download-state" name="estado_id" x-model="estado" x-on:change="carregarMunicipios()" x-bind:disabled="!brasil" x-bind:required="brasil" class="public-control" @error('estado_id') aria-invalid="true" aria-describedby="download-state-error" @enderror>
                                    <option value="">Selecione</option>
                                    @foreach ($estados as $estado)
                                        <option value="{{ $estado->id }}" @selected((string) old('estado_id') === (string) $estado->id)>{{ $estado->nome }}</option>
                                    @endforeach
                                </select>
                                @error('estado_id') <p id="download-state-error" class="public-field-error mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div x-show="brasil" x-cloak>
                                <label for="download-city" class="public-label mb-2 block">Cidade <span class="public-required" aria-hidden="true">*</span></label>
                                <select
                                    id="download-city"
                                    name="municipio_id"
                                    x-model="municipio"
                                    x-bind:disabled="!brasil || !estado || carregando"
                                    x-bind:required="brasil"
                                    class="public-control"
                                    aria-describedby="download-city-status @error('municipio_id') download-city-error @enderror"
                                    @error('municipio_id') aria-invalid="true" @enderror
                                >
                                    <option value="" x-text="estado ? 'Selecione' : 'Escolha o estado primeiro'">Selecione</option>
                                    <template x-for="cidade in municipios" :key="cidade.id">
                                        <option x-bind:value="cidade.id" x-text="cidade.nome" x-bind:selected="String(cidade.id) === municipio"></option>
                                    </template>
                                </select>
                                <p id="download-city-status" class="mt-1 text-[0.82rem] leading-[1.5] text-ink-600 empty:hidden" aria-live="polite" x-text="statusCidades"></p>
                                @error('municipio_id') <p id="download-city-error" class="public-field-error mt-1">{{ $message }}</p> @enderror
                            </div>
                        </fieldset>
                        <div>
                            <label for="download-purpose" class="public-label mb-2 block">Como pretende usar a fotografia? <span class="public-required" aria-hidden="true">*</span></label>
                            <textarea id="download-purpose" name="finalidade" rows="4" minlength="10" maxlength="1000" required placeholder="Ex.: pesquisa escolar sobre a história do bairro" class="public-control resize-y" @error('finalidade') aria-invalid="true" aria-describedby="download-purpose-error" @enderror>{{ old('finalidade') }}</textarea>
                            @error('finalidade') <p id="download-purpose-error" class="public-field-error mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="border-l-[3px] border-institutional bg-institutional-soft px-5 py-4 text-[0.88rem] leading-[1.65] text-ink-800">
                            <h2 class="font-bold text-institutional">Condições de uso</h2>
                            <p class="mt-2">Ao citar ou divulgar esta imagem, identifique o autor quando informado e cite “Memórias Vivas de Umuarama”, o título e o número do registro. O download não transfere direitos autorais nem autoriza, por si só, publicação ou uso comercial; confirme as permissões necessárias para o uso pretendido.</p>
                            <p class="mt-2">Nome, e-mail, perfil, localidade (país e, no Brasil, estado e cidade) e finalidade são armazenados para registrar esta solicitação de acesso ao acervo.</p>
                        </div>
                        <div>
                            <label for="download-accept" class="flex items-start gap-3 text-[0.88rem] leading-[1.55] text-ink-800">
                                <input id="download-accept" name="aceite" type="checkbox" value="1" required @checked(old('aceite')) class="mt-1 size-5 flex-none accent-institutional" @error('aceite') aria-invalid="true" aria-describedby="download-accept-error" @enderror>
                                <span>Li as condições de uso e me comprometo a indicar a fonte e a respeitar os direitos associados à fotografia. <span class="public-required" aria-hidden="true">*</span></span>
                            </label>
                            @error('aceite') <p id="download-accept-error" class="public-field-error mt-1">{{ $message }}</p> @enderror
                        </div>
                        <x-public.button type="submit" class="w-fit">Confirmar e baixar <span aria-hidden="true">&darr;</span></x-public.button>
                    </form>
                </div>
            </div>
        </x-public.container>
    </section>
</x-layouts.public>
