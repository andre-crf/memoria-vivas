<x-layouts.public title="Sobre nós | Memórias Vivas">
    <section class="public-about-hero">
        <div class="public-container public-about-hero__inner">
            <div class="public-about-hero__copy">
                <p class="public-eyebrow">Sobre o projeto</p>
                <h1>Umuarama também se conta pelas suas memórias.</h1>
                <p class="public-about-hero__lead">
                    Memórias Vivas reúne fotografias e informações do acervo para preservar histórias da cidade e aproximá-las de quem deseja conhecer, pesquisar e compartilhar.
                </p>
                <div class="public-about-hero__actions">
                    <a href="{{ route('public.catalogo') }}" class="public-button">Explorar o catálogo <span aria-hidden="true">&rarr;</span></a>
                    <a href="{{ route('public.colaborar') }}" class="public-text-link">Conheça formas de colaborar</a>
                </div>
            </div>
            <div class="public-about-hero__identity">
                <img src="{{ asset('images/memorias-vivas-logo.png') }}" alt="Memórias Vivas de Umuarama" width="180" height="180">
                <p>Memórias Vivas<br><span>Acervo de Umuarama</span></p>
            </div>
        </div>
    </section>

    <section class="public-section public-about-purpose" aria-labelledby="about-purpose-title">
        <div class="public-container public-about-purpose__inner">
            <div>
                <p class="public-eyebrow">Nosso propósito</p>
                <h2 id="about-purpose-title">Uma fotografia ganha sentido quando sua história pode ser encontrada.</h2>
            </div>
            <div class="public-section__copy">
                <p>O projeto organiza registros fotográficos e as informações que ajudam a compreendê-los: datas, lugares, pessoas, acontecimentos e temas relacionados.</p>
                <p>Assim, cada item publicado pode ser consultado em seu contexto, e as memórias de Umuarama ficam mais acessíveis para diferentes gerações.</p>
            </div>
        </div>
    </section>

    <section class="public-section public-section--tinted public-about-process" aria-labelledby="about-process-title">
        <div class="public-container">
            <div class="public-about-process__heading">
                <p class="public-eyebrow">Do acervo à consulta</p>
                <h2 id="about-process-title">Cuidado em cada etapa</h2>
                <p>Os registros passam por organização e revisão antes de serem apresentados ao público.</p>
            </div>
            <ol class="public-about-steps">
                <li>
                    <span aria-hidden="true">01</span>
                    <div>
                        <h3>Preservar</h3>
                        <p>Manter o arquivo original e preparar versões adequadas para consulta digital.</p>
                    </div>
                </li>
                <li>
                    <span aria-hidden="true">02</span>
                    <div>
                        <h3>Organizar</h3>
                        <p>Associar informações descritivas que ajudam a identificar e contextualizar cada fotografia.</p>
                    </div>
                </li>
                <li>
                    <span aria-hidden="true">03</span>
                    <div>
                        <h3>Compartilhar</h3>
                        <p>Publicar os registros revisados em um catálogo aberto para explorar e pesquisar.</p>
                    </div>
                </li>
            </ol>
        </div>
    </section>

    <section class="public-about-invite" aria-labelledby="about-invite-title">
        <div class="public-container public-about-invite__inner">
            <div>
                <p class="public-eyebrow">Memória em movimento</p>
                <h2 id="about-invite-title">Toda consulta pode revelar uma nova história.</h2>
            </div>
            <a href="{{ route('public.catalogo') }}" class="public-button">Ver fotografias do acervo <span aria-hidden="true">&rarr;</span></a>
        </div>
    </section>
</x-layouts.public>
