<x-layouts.public title="Como colaborar | Memórias Vivas">
    @php
        $h2 = 'mt-[0.8rem] max-w-[42rem] text-[length:clamp(1.8rem,4vw,3rem)] leading-[1.12] font-semibold';
        $field = 'grid min-w-0 content-start gap-2';
        $optional = 'font-normal text-ink-450';
        $fieldset = 'grid min-w-0 gap-4 border-t border-mist-350 pt-[1.4rem]';
        $legend = 'pr-4 font-serif text-[1.35rem] font-semibold text-institutional';
        $fieldsGrid = 'grid grid-cols-1 gap-4 md:grid-cols-2';
    @endphp

    <x-public.page-hero
        :tinted="false"
        eyebrow="Como colaborar"
        title="Sua memória também faz parte do acervo."
        lead="Um nome, um lugar ou uma data podem ajudar a compreender uma fotografia. Conheça maneiras de contribuir com as histórias de Umuarama."
    >
        <x-public.button href="#enviar">Enviar fotografias <span aria-hidden="true">&rarr;</span></x-public.button>
        <x-public.text-link :href="route('public.catalogo')">Explorar o catálogo</x-public.text-link>
    </x-public.page-hero>

    <x-public.section id="enviar" class="scroll-mt-8" aria-labelledby="contribution-title">
        <x-public.container class="grid grid-cols-1 gap-[clamp(2rem,6vw,6rem)] md:grid-cols-[minmax(0,0.72fr)_minmax(0,1fr)]">
            <div class="self-start md:sticky md:top-8">
                <x-public.eyebrow>Compartilhe seu acervo</x-public.eyebrow>
                <x-public.heading id="contribution-title" class="{{ $h2 }}">Envie suas fotografias</x-public.heading>
                <p class="mt-[1.2rem] max-w-[29rem] text-[0.98rem] leading-[1.75] text-ink-600">Suas imagens chegam à equipe do Memórias Vivas para análise. O envio não publica as fotografias automaticamente.</p>
                <div class="mt-6 border-t border-mist-350 md:mt-10">
                    @foreach ([
                        'Escolha até cinco imagens JPG, PNG ou WebP, com até 10 MB cada.',
                        'Conte o que sabe. Não tem certeza da data ou do local? Pode deixar em branco.',
                        'Depois do envio, a equipe poderá falar com você pelo e-mail informado.',
                    ] as $nota)
                        <p class="grid grid-cols-[2rem_1fr] gap-3 border-b border-mist-350 py-4 text-[0.9rem] leading-[1.6] text-ink-700"><strong class="text-[0.75rem] text-institutional">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</strong> {{ $nota }}</p>
                    @endforeach
                </div>
            </div>

            <div>
                @if (session('contribuicao_enviada'))
                    <x-ui.alert tone="success" title="Fotografias recebidas.">
                        <p class="mb-3">Obrigado por colaborar com a memória de Umuarama. Sua contribuição ficará reservada até a análise da equipe.</p>
                        <x-public.text-link :href="route('public.colaborar').'#enviar'">Enviar outras fotografias</x-public.text-link>
                    </x-ui.alert>
                @else
                    @if ($errors->any())
                        <x-ui.alert tone="danger" title="Confira os campos indicados abaixo." class="mb-6">
                            <p>As fotografias precisam ser selecionadas novamente após a correção.</p>
                        </x-ui.alert>
                    @endif

                    <form method="POST" action="{{ route('public.colaborar.store') }}" enctype="multipart/form-data" class="grid gap-8" x-data="contributionForm()" @submit="submitting = true">
                        @csrf
                        <p class="-mb-4 text-[0.82rem] text-ink-600"><span class="public-required" aria-hidden="true">*</span> Campo obrigatório</p>
                        <fieldset class="{{ $fieldset }}">
                            <legend class="{{ $legend }}">Seus dados</legend>
                            <div class="{{ $fieldsGrid }}">
                                <div class="{{ $field }}">
                                    <label for="contribution-name" class="public-label">Seu nome <span class="public-required" aria-hidden="true">*</span></label>
                                    <input id="contribution-name" name="nome" type="text" value="{{ old('nome') }}" autocomplete="name" maxlength="120" required class="public-control" @error('nome') aria-invalid="true" aria-describedby="contribution-name-error" @enderror>
                                    @error('nome') <p id="contribution-name-error" class="public-field-error">{{ $message }}</p> @enderror
                                </div>
                                <div class="{{ $field }}">
                                    <label for="contribution-email" class="public-label">E-mail para contato <span class="public-required" aria-hidden="true">*</span></label>
                                    <input id="contribution-email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" maxlength="255" required class="public-control" @error('email') aria-invalid="true" aria-describedby="contribution-email-error" @enderror>
                                    @error('email') <p id="contribution-email-error" class="public-field-error">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </fieldset>

                        <fieldset class="{{ $fieldset }}">
                            <legend class="{{ $legend }}">Sobre as fotografias</legend>
                            <div class="{{ $field }}">
                                <label for="contribution-title-input" class="public-label">Título ou assunto <span class="public-required" aria-hidden="true">*</span></label>
                                <input id="contribution-title-input" name="titulo" type="text" value="{{ old('titulo') }}" placeholder="Ex.: Festa na Praça Arthur Thomas" maxlength="180" required class="public-control" @error('titulo') aria-invalid="true" aria-describedby="contribution-title-error" @enderror>
                                @error('titulo') <p id="contribution-title-error" class="public-field-error">{{ $message }}</p> @enderror
                            </div>
                            <div class="{{ $fieldsGrid }}">
                                <div class="{{ $field }}">
                                    <label for="contribution-place" class="public-label">Local retratado <span class="{{ $optional }}">opcional</span></label>
                                    <input id="contribution-place" name="local" type="text" value="{{ old('local') }}" placeholder="Bairro, rua ou ponto de referência" maxlength="180" class="public-control" @error('local') aria-invalid="true" aria-describedby="contribution-place-error" @enderror>
                                    @error('local') <p id="contribution-place-error" class="public-field-error">{{ $message }}</p> @enderror
                                </div>
                                <div class="{{ $field }}">
                                    <label for="contribution-period" class="public-label">Época aproximada <span class="{{ $optional }}">opcional</span></label>
                                    <input id="contribution-period" name="periodo" type="text" value="{{ old('periodo') }}" placeholder="Ex.: década de 1980" maxlength="100" class="public-control" @error('periodo') aria-invalid="true" aria-describedby="contribution-period-error" @enderror>
                                    @error('periodo') <p id="contribution-period-error" class="public-field-error">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <div class="{{ $field }}">
                                <label for="contribution-story" class="public-label">O que você sabe sobre essas imagens? <span class="{{ $optional }}">opcional</span></label>
                                <textarea id="contribution-story" name="historia" rows="4" maxlength="3000" placeholder="Pessoas, evento, autoria ou a história por trás do registro..." class="public-control resize-y" @error('historia') aria-invalid="true" aria-describedby="contribution-story-error" @enderror>{{ old('historia') }}</textarea>
                                @error('historia') <p id="contribution-story-error" class="public-field-error">{{ $message }}</p> @enderror
                            </div>
                        </fieldset>

                        <fieldset class="{{ $fieldset }}">
                            <legend class="{{ $legend }}">Arquivos</legend>
                            <div class="{{ $field }}">
                                <label for="contribution-photos" class="public-label grid cursor-pointer justify-items-center gap-[0.3rem] rounded border border-dashed border-mist-600 bg-mist-100 p-6 text-center hover:border-institutional hover:bg-institutional-soft [&:has(+input:focus-visible)]:outline-3 [&:has(+input:focus-visible)]:outline-offset-2 [&:has(+input:focus-visible)]:outline-institutional">
                                    <span class="grid size-[2.2rem] place-items-center rounded-full border border-institutional text-[1.4rem] leading-none font-normal" aria-hidden="true">+</span>
                                    <strong>Escolher fotografias</strong>
                                    <span class="text-[0.8rem] font-normal text-ink-600">JPG, PNG ou WebP · até 5 arquivos de 10 MB</span>
                                </label>
                                <input id="contribution-photos" class="absolute size-px overflow-hidden opacity-0" name="fotos[]" type="file" accept="image/jpeg,image/png,image/webp" multiple required @change="selectFiles($event)" @if ($errors->has('fotos') || $errors->has('fotos.*')) aria-invalid="true" aria-describedby="{{ trim(($errors->has('fotos') ? 'contribution-photos-error ' : '').($errors->has('fotos.*') ? 'contribution-photos-items-error' : '')) }}" @endif>
                                <p x-show="fileError" x-text="fileError" class="public-field-error" role="alert"></p>
                                @error('fotos') <p id="contribution-photos-error" class="public-field-error">{{ $message }}</p> @enderror
                                @error('fotos.*') <p id="contribution-photos-items-error" class="public-field-error">{{ $message }}</p> @enderror
                                <ul x-show="files.length" class="mt-2 grid grid-cols-2 gap-[0.8rem] md:grid-cols-3" aria-label="Fotografias selecionadas">
                                    <template x-for="file in files" :key="file.url">
                                        <li class="min-w-0 overflow-hidden rounded border border-mist-200 bg-white"><img :src="file.url" alt="Prévia da fotografia selecionada" class="aspect-4/3 w-full object-cover"><span x-text="file.name" class="block truncate px-2 py-[0.4rem] text-[0.72rem] text-ink-700"></span></li>
                                    </template>
                                </ul>
                            </div>
                        </fieldset>

                        {{-- Mensagem de erro junto da caixa de seleção, no mesmo bloco. --}}
                        <div class="grid gap-2">
                            <div class="flex items-start gap-3">
                                <input id="contribution-consent" name="autorizacao" type="checkbox" value="1" required class="mt-[0.15rem] size-[1.1rem] flex-none accent-institutional" @checked(old('autorizacao')) @error('autorizacao') aria-invalid="true" aria-describedby="contribution-consent-error" @enderror>
                                <label for="contribution-consent" class="text-[0.86rem] leading-[1.6] text-ink-700">Declaro que tenho permissão para compartilhar estas fotografias com a equipe do Memórias Vivas para avaliação. O envio não autoriza a publicação automática. <span class="public-required" aria-hidden="true">*</span></label>
                            </div>
                            @error('autorizacao') <p id="contribution-consent-error" class="public-field-error pl-[calc(1.1rem+0.75rem)]">{{ $message }}</p> @enderror
                        </div>
                        <x-public.button type="submit" class="w-fit min-w-52 justify-center" ::disabled="submitting" ::aria-busy="submitting"><span x-text="submitting ? 'Enviando...' : 'Enviar fotografias'"></span><span aria-hidden="true">&rarr;</span></x-public.button>
                    </form>
                @endif
            </div>
        </x-public.container>
    </x-public.section>

    <x-public.section id="formas" aria-labelledby="collaborate-ways-title">
        <x-public.container>
            <x-public.eyebrow>Formas de participar</x-public.eyebrow>
            <x-public.heading id="collaborate-ways-title" class="{{ $h2 }}">Toda informação ajuda a contar melhor uma história.</x-public.heading>
            <x-public.steps class="mt-10" :items="[
                ['Reconheça registros', 'Ao consultar o catálogo, observe se você identifica pessoas, lugares, acontecimentos ou uma data aproximada.'],
                ['Compartilhe contexto', 'Relatos sobre a ocasião, a autoria da imagem ou a relação com a cidade ajudam a situar cada fotografia.'],
                ['Separe fotografias', 'Se você guarda registros de Umuarama, preserve o arquivo ou a cópia em melhor qualidade e anote o que sabe sobre eles.'],
            ]" />
        </x-public.container>
    </x-public.section>

    <x-public.section tinted aria-labelledby="collaborate-prepare-title">
        <x-public.container class="grid grid-cols-1 gap-8 md:grid-cols-[minmax(0,0.85fr)_minmax(0,1.15fr)] md:gap-16">
            <div>
                <x-public.eyebrow>Antes de compartilhar</x-public.eyebrow>
                <x-public.heading id="collaborate-prepare-title" class="{{ $h2 }}">O que vale a pena reunir</x-public.heading>
                <p class="mt-[1.2rem] max-w-[32rem] text-[0.95rem] leading-[1.75] text-ink-600">Não é preciso conhecer todos os detalhes. Mesmo informações aproximadas podem ajudar na identificação do registro.</p>
            </div>
            <ul class="grid list-none">
                @foreach ([
                    'A fotografia original ou a cópia digital em melhor qualidade disponível.',
                    'Data ou período aproximado e o local retratado, quando conhecidos.',
                    'Nomes das pessoas, do evento e de quem fez ou guardou a fotografia.',
                    'Informações sobre autoria e permissão para compartilhar o registro publicamente.',
                ] as $item)
                    <li class="relative border-t border-mist-400 py-4 pl-8 text-[0.95rem] leading-[1.65] text-ink-800 before:absolute before:top-[1.2rem] before:left-0 before:size-[0.65rem] before:rounded-full before:border-2 before:border-institutional">{{ $item }}</li>
                @endforeach
            </ul>
        </x-public.container>
    </x-public.section>

    <x-public.invite eyebrow="Próximo passo" title="Vamos construir esse acervo juntos." title-id="collaborate-invite-title">
        <p>Prefere conversar antes de enviar? Escreva para <a href="mailto:memoriasvivasumuarama@gmail.com">memoriasvivasumuarama@gmail.com</a>.</p>

        <x-slot:action>
            <x-public.button href="mailto:memoriasvivasumuarama@gmail.com?subject=Colaborar%20com%20o%20acervo" class="flex-none">Enviar e-mail <span aria-hidden="true">&rarr;</span></x-public.button>
        </x-slot:action>
    </x-public.invite>
</x-layouts.public>
