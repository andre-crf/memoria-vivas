<x-layouts.public :title="$page['title'] . ' | Memórias Vivas'">
    <section class="public-institutional">
        <div class="public-container public-institutional__inner">
            <div class="public-institutional__heading">
                <p class="public-eyebrow">{{ $page['eyebrow'] }}</p>
                <h1>{{ $page['heading'] }}</h1>
                <p class="public-institutional__lead">{{ $page['intro'] }}</p>
            </div>

            <aside class="public-institutional__note" aria-label="Conteúdo da página">
                <span class="public-institutional__note-mark" aria-hidden="true">i</span>
                <div>
                    <h2>Conteúdo em preparação</h2>
                    <p>{{ $page['notice'] }}</p>
                </div>
            </aside>
        </div>
    </section>
</x-layouts.public>
