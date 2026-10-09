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
</x-layouts.public>
