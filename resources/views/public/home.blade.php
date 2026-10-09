<x-layouts.public title="Memórias Vivas | Acervo público">
    <section class="public-hero">
        <div class="public-container public-hero__grid">
            <div class="public-hero__content">
                <p class="public-eyebrow">Acervo público</p>
                <h1>Memórias Vivas de Umuarama</h1>
                <p class="public-hero__lead">
                    Um espaço para conhecer, preservar e compartilhar registros da memória de Umuarama.
                </p>
                <div class="public-hero__actions">
                    <a href="{{ route('public.catalogo') }}" class="public-button">Explorar o catálogo <span aria-hidden="true">&rarr;</span></a>
                    <a href="#sobre" class="public-text-link">Sobre o projeto</a>
                </div>
            </div>

            <div class="public-hero__identity" aria-label="Identidade visual do projeto">
                <img
                    src="{{ asset('images/memorias-vivas-logo.jpg') }}"
                    alt="Logo Memórias Vivas de Umuarama"
                    width="180"
                    height="180"
                >
                <p>Preservar o passado para ampliar o acesso às histórias da cidade.</p>
            </div>
        </div>
    </section>

    <section id="sobre" class="public-section">
        <div class="public-container public-section__grid">
            <div>
                <p class="public-eyebrow">O acervo</p>
                <h2>Histórias que permanecem acessíveis</h2>
            </div>
            <div class="public-section__copy">
                <p>A área pública está sendo preparada para apresentar fotografias e documentos catalogados, com contexto e informações que ajudam a reconhecer cada registro.</p>
                <p>Os conteúdos serão disponibilizados conforme as etapas de organização e revisão do acervo forem concluídas.</p>
            </div>
        </div>
    </section>

    <section class="public-section public-section--tinted">
        <div class="public-container public-introduction">
            <div>
                <p class="public-eyebrow">Como consultar</p>
                <h2>Uma porta de entrada para a memória local</h2>
            </div>
            <div class="public-introduction__items">
                <div class="public-introduction__item">
                    <span class="public-introduction__number">01</span>
                    <div>
                        <h3>Explore</h3>
                        <p>Consulte os registros públicos disponíveis no catálogo.</p>
                    </div>
                </div>
                <div class="public-introduction__item">
                    <span class="public-introduction__number">02</span>
                    <div>
                        <h3>Reconheça</h3>
                        <p>Conheça os contextos e as informações de cada fotografia.</p>
                    </div>
                </div>
                <div class="public-introduction__item">
                    <span class="public-introduction__number">03</span>
                    <div>
                        <h3>Compartilhe</h3>
                        <p>Leve adiante as memórias que ajudam a contar a história de Umuarama.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="public-section public-recent" aria-labelledby="recentes-titulo">
        <div class="public-container">
            <div class="public-section__heading">
                <div>
                    <p class="public-eyebrow">Em destaque</p>
                    <h2 id="recentes-titulo">Fotografias recentes</h2>
                </div>
                <a href="{{ route('public.catalogo') }}" class="public-text-link">Ver catálogo <span aria-hidden="true">&rarr;</span></a>
            </div>

            @if ($fotografias->isNotEmpty())
                <div class="public-carousel" data-carousel tabindex="0" aria-roledescription="carrossel" aria-label="Fotografias recentes">
                    <button type="button" class="public-carousel__control public-carousel__control--prev" data-carousel-prev aria-label="Fotografia anterior">&larr;</button>
                    <div class="public-carousel__viewport">
                        <div class="public-carousel__track" data-carousel-track>
                            @foreach ($fotografias as $fotografia)
                                @php($imagem = $fotografia->imagemAdministrativa(['large', 'medium', 'thumbnail']))
                                <article class="public-carousel__slide" data-carousel-slide aria-label="{{ $loop->iteration }} de {{ $fotografias->count() }}">
                                    <a href="{{ route('public.fotografias.show', $fotografia) }}" class="public-recent-item">
                                        <div class="public-recent-item__image">
                                            @if ($imagem)
                                                <img src="{{ route('publico.imagens.show', $imagem) }}" alt="{{ $fotografia->titulo }}" loading="lazy" width="640" height="420">
                                            @else
                                                <span aria-hidden="true">Sem imagem disponível</span>
                                            @endif
                                        </div>
                                        <div class="public-recent-item__body">
                                            <h3>{{ $fotografia->titulo }}</h3>
                                            <p>{{ $fotografia->dataHistorica()->label() }}</p>
                                            <span class="public-recent-item__link">Ver detalhes <span aria-hidden="true">&rarr;</span></span>
                                        </div>
                                    </a>
                                </article>
                            @endforeach
                        </div>
                    </div>
                    <button type="button" class="public-carousel__control public-carousel__control--next" data-carousel-next aria-label="Próxima fotografia">&rarr;</button>
                    <div class="public-carousel__dots" data-carousel-dots aria-label="Selecionar fotografia"></div>
                </div>
            @else
                <div class="public-empty-state public-empty-state--recent">
                    <strong>Nenhuma fotografia pública disponível ainda</strong>
                    <span>As fotografias publicadas aparecerão aqui conforme o acervo for revisado.</span>
                </div>
            @endif
        </div>
    </section>
</x-layouts.public>

@if ($fotografias->isNotEmpty())
    <script>
        document.querySelectorAll('[data-carousel]').forEach((carousel) => {
            const track = carousel.querySelector('[data-carousel-track]');
            const slides = [...carousel.querySelectorAll('[data-carousel-slide]')];
            const dots = carousel.querySelector('[data-carousel-dots]');
            let current = 0;

            const update = (index) => {
                current = (index + slides.length) % slides.length;
                track.style.transform = `translateX(-${current * 100}%)`;
                slides.forEach((slide, slideIndex) => slide.setAttribute('aria-hidden', slideIndex === current ? 'false' : 'true'));
                [...dots.children].forEach((dot, dotIndex) => dot.setAttribute('aria-current', dotIndex === current ? 'true' : 'false'));
            };

            slides.forEach((slide, index) => {
                const dot = document.createElement('button');
                dot.type = 'button';
                dot.className = 'public-carousel__dot';
                dot.setAttribute('aria-label', `Exibir fotografia ${index + 1}`);
                dot.addEventListener('click', () => update(index));
                dots.append(dot);
            });

            carousel.querySelector('[data-carousel-prev]').addEventListener('click', () => update(current - 1));
            carousel.querySelector('[data-carousel-next]').addEventListener('click', () => update(current + 1));
            carousel.addEventListener('keydown', (event) => {
                if (event.key === 'ArrowLeft') update(current - 1);
                if (event.key === 'ArrowRight') update(current + 1);
            });
            update(0);
        });
    </script>
@endif
