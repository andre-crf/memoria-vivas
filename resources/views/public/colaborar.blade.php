<x-layouts.public title="Como colaborar | Memórias Vivas">
    <section class="public-about-hero public-collaborate-hero">
        <div class="public-container public-about-hero__inner">
            <div class="public-about-hero__copy">
                <p class="public-eyebrow">Como colaborar</p>
                <h1>Sua memória também faz parte do acervo.</h1>
                <p class="public-about-hero__lead">
                    Um nome, um lugar ou uma data podem ajudar a compreender uma fotografia. Conheça maneiras de contribuir com as histórias de Umuarama.
                </p>
                <div class="public-about-hero__actions">
                    <a href="#enviar" class="public-button">Enviar fotografias <span aria-hidden="true">&rarr;</span></a>
                    <a href="{{ route('public.catalogo') }}" class="public-text-link">Explorar o catálogo</a>
                </div>
            </div>
            <div class="public-about-hero__identity">
                <img src="{{ asset('images/memorias-vivas-logo.png') }}" alt="Memórias Vivas de Umuarama" width="180" height="180">
                <p>Memórias Vivas<br><span>Acervo de Umuarama</span></p>
            </div>
        </div>
    </section>

    <section id="enviar" class="public-section public-contribution" aria-labelledby="contribution-title">
        <div class="public-container public-contribution__grid">
            <div class="public-contribution__intro">
                <p class="public-eyebrow">Compartilhe seu acervo</p>
                <h2 id="contribution-title">Envie suas fotografias</h2>
                <p>Suas imagens chegam à equipe do Memórias Vivas para análise. O envio não publica as fotografias automaticamente.</p>
                <div class="public-contribution__notes">
                    <p><strong>01</strong> Escolha até cinco imagens JPG, PNG ou WebP, com até 10 MB cada.</p>
                    <p><strong>02</strong> Conte o que sabe. Não tem certeza da data ou do local? Pode deixar em branco.</p>
                    <p><strong>03</strong> Depois do envio, a equipe poderá falar com você pelo e-mail informado.</p>
                </div>
            </div>

            <div class="public-contribution__form-wrap">
                @if (session('contribuicao_enviada'))
                    <div class="public-contribution__success" role="status">
                        <strong>Fotografias recebidas.</strong>
                        <p>Obrigado por colaborar com a memória de Umuarama. Sua contribuição ficará reservada até a análise da equipe.</p>
                        <a href="{{ route('public.colaborar') }}#enviar" class="public-text-link">Enviar outras fotografias</a>
                    </div>
                @else
                    @if ($errors->any())
                        <div class="public-contribution__error-summary" role="alert">
                            <strong>Confira os campos indicados abaixo.</strong>
                            <p>As fotografias precisam ser selecionadas novamente após a correção.</p>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('public.colaborar.store') }}" enctype="multipart/form-data" class="public-contribution__form" x-data="contributionForm()" @submit="submitting = true">
                        @csrf
                        <fieldset>
                            <legend>Seus dados</legend>
                            <div class="public-contribution__fields">
                                <div class="public-contribution__field">
                                    <label for="contribution-name">Seu nome <span aria-hidden="true">*</span></label>
                                    <input id="contribution-name" name="nome" type="text" value="{{ old('nome') }}" autocomplete="name" maxlength="120" required @error('nome') aria-invalid="true" aria-describedby="contribution-name-error" @enderror>
                                    @error('nome') <p id="contribution-name-error" class="public-contribution__field-error">{{ $message }}</p> @enderror
                                </div>
                                <div class="public-contribution__field">
                                    <label for="contribution-email">E-mail para contato <span aria-hidden="true">*</span></label>
                                    <input id="contribution-email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" maxlength="255" required @error('email') aria-invalid="true" aria-describedby="contribution-email-error" @enderror>
                                    @error('email') <p id="contribution-email-error" class="public-contribution__field-error">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </fieldset>

                        <fieldset>
                            <legend>Sobre as fotografias</legend>
                            <div class="public-contribution__field">
                                <label for="contribution-title-input">Título ou assunto <span aria-hidden="true">*</span></label>
                                <input id="contribution-title-input" name="titulo" type="text" value="{{ old('titulo') }}" placeholder="Ex.: Festa na Praça Arthur Thomas" maxlength="180" required @error('titulo') aria-invalid="true" aria-describedby="contribution-title-error" @enderror>
                                @error('titulo') <p id="contribution-title-error" class="public-contribution__field-error">{{ $message }}</p> @enderror
                            </div>
                            <div class="public-contribution__fields">
                                <div class="public-contribution__field">
                                    <label for="contribution-place">Local retratado <span class="public-contribution__optional">opcional</span></label>
                                    <input id="contribution-place" name="local" type="text" value="{{ old('local') }}" placeholder="Bairro, rua ou ponto de referência" maxlength="180">
                                </div>
                                <div class="public-contribution__field">
                                    <label for="contribution-period">Época aproximada <span class="public-contribution__optional">opcional</span></label>
                                    <input id="contribution-period" name="periodo" type="text" value="{{ old('periodo') }}" placeholder="Ex.: década de 1980" maxlength="100">
                                </div>
                            </div>
                            <div class="public-contribution__field">
                                <label for="contribution-story">O que você sabe sobre essas imagens? <span class="public-contribution__optional">opcional</span></label>
                                <textarea id="contribution-story" name="historia" rows="4" maxlength="3000" placeholder="Pessoas, evento, autoria ou a história por trás do registro...">{{ old('historia') }}</textarea>
                                @error('historia') <p class="public-contribution__field-error">{{ $message }}</p> @enderror
                            </div>
                        </fieldset>

                        <fieldset>
                            <legend>Arquivos</legend>
                            <div class="public-contribution__field">
                                <label for="contribution-photos" class="public-contribution__upload">
                                    <span class="public-contribution__upload-icon" aria-hidden="true">+</span>
                                    <strong>Escolher fotografias</strong>
                                    <span>JPG, PNG ou WebP · até 5 arquivos de 10 MB</span>
                                </label>
                                <input id="contribution-photos" class="public-contribution__file-input" name="fotos[]" type="file" accept="image/jpeg,image/png,image/webp" multiple required @change="selectFiles($event)" @error('fotos') aria-invalid="true" aria-describedby="contribution-photos-error" @enderror>
                                <p x-show="fileError" x-text="fileError" class="public-contribution__field-error" role="alert"></p>
                                @error('fotos') <p id="contribution-photos-error" class="public-contribution__field-error">{{ $message }}</p> @enderror
                                @error('fotos.*') <p class="public-contribution__field-error">{{ $message }}</p> @enderror
                                <ul x-show="files.length" class="public-contribution__previews" aria-label="Fotografias selecionadas">
                                    <template x-for="file in files" :key="file.url">
                                        <li><img :src="file.url" alt="Prévia da fotografia selecionada"><span x-text="file.name"></span></li>
                                    </template>
                                </ul>
                            </div>
                        </fieldset>

                        <div class="public-contribution__consent">
                            <input id="contribution-consent" name="autorizacao" type="checkbox" value="1" required @checked(old('autorizacao')) @error('autorizacao') aria-invalid="true" aria-describedby="contribution-consent-error" @enderror>
                            <label for="contribution-consent">Declaro que tenho permissão para compartilhar estas fotografias com a equipe do Memórias Vivas para avaliação. O envio não autoriza a publicação automática. <span aria-hidden="true">*</span></label>
                        </div>
                        @error('autorizacao') <p id="contribution-consent-error" class="public-contribution__field-error">{{ $message }}</p> @enderror
                        <button type="submit" class="public-button public-contribution__submit" :disabled="submitting"><span x-text="submitting ? 'Enviando...' : 'Enviar fotografias'"></span><span aria-hidden="true">&rarr;</span></button>
                    </form>
                @endif
            </div>
        </div>
    </section>

    <section id="formas" class="public-section public-collaborate-ways" aria-labelledby="collaborate-ways-title">
        <div class="public-container">
            <p class="public-eyebrow">Formas de participar</p>
            <h2 id="collaborate-ways-title">Toda informação ajuda a contar melhor uma história.</h2>
            <ol class="public-about-steps">
                <li>
                    <span aria-hidden="true">01</span>
                    <div>
                        <h3>Reconheça registros</h3>
                        <p>Ao consultar o catálogo, observe se você identifica pessoas, lugares, acontecimentos ou uma data aproximada.</p>
                    </div>
                </li>
                <li>
                    <span aria-hidden="true">02</span>
                    <div>
                        <h3>Compartilhe contexto</h3>
                        <p>Relatos sobre a ocasião, a autoria da imagem ou a relação com a cidade ajudam a situar cada fotografia.</p>
                    </div>
                </li>
                <li>
                    <span aria-hidden="true">03</span>
                    <div>
                        <h3>Separe fotografias</h3>
                        <p>Se você guarda registros de Umuarama, preserve o arquivo ou a cópia em melhor qualidade e anote o que sabe sobre eles.</p>
                    </div>
                </li>
            </ol>
        </div>
    </section>

    <section class="public-section public-section--tinted public-collaborate-prepare" aria-labelledby="collaborate-prepare-title">
        <div class="public-container public-collaborate-prepare__inner">
            <div>
                <p class="public-eyebrow">Antes de compartilhar</p>
                <h2 id="collaborate-prepare-title">O que vale a pena reunir</h2>
                <p>Não é preciso conhecer todos os detalhes. Mesmo informações aproximadas podem ajudar na identificação do registro.</p>
            </div>
            <ul class="public-collaborate-checklist">
                <li>A fotografia original ou a cópia digital em melhor qualidade disponível.</li>
                <li>Data ou período aproximado e o local retratado, quando conhecidos.</li>
                <li>Nomes das pessoas, do evento e de quem fez ou guardou a fotografia.</li>
                <li>Informações sobre autoria e permissão para compartilhar o registro publicamente.</li>
            </ul>
        </div>
    </section>

    <section class="public-about-invite public-collaborate-invite" aria-labelledby="collaborate-invite-title">
        <div class="public-container public-about-invite__inner">
            <div>
                <p class="public-eyebrow">Próximo passo</p>
                <h2 id="collaborate-invite-title">Vamos construir esse acervo juntos.</h2>
                <p>Prefere conversar antes de enviar? Escreva para <a href="mailto:memoriasvivasumuarama@gmail.com">memoriasvivasumuarama@gmail.com</a>.</p>
            </div>
            <a href="mailto:memoriasvivasumuarama@gmail.com?subject=Colaborar%20com%20o%20acervo" class="public-button">Enviar e-mail <span aria-hidden="true">&rarr;</span></a>
        </div>
    </section>
</x-layouts.public>
