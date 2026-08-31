<?php

namespace App\Providers;

use App\Models\Arquivo;
use App\Models\Assunto;
use App\Models\Autor;
use App\Models\Categoria;
use App\Models\Colecao;
use App\Models\ConjuntoContextual;
use App\Models\ItemAcervo;
use App\Models\PalavraChave;
use App\Models\Pessoa;
use App\Models\User;
use App\Policies\ArquivoPolicy;
use App\Policies\ItemAcervoPolicy;
use App\Policies\SupportCatalogPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(ItemAcervo::class, ItemAcervoPolicy::class);
        Gate::policy(Arquivo::class, ArquivoPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        Gate::policy(Categoria::class, SupportCatalogPolicy::class);
        Gate::policy(Assunto::class, SupportCatalogPolicy::class);
        Gate::policy(PalavraChave::class, SupportCatalogPolicy::class);
        Gate::policy(Pessoa::class, SupportCatalogPolicy::class);
        Gate::policy(Autor::class, SupportCatalogPolicy::class);
        Gate::policy(Colecao::class, SupportCatalogPolicy::class);
        Gate::policy(ConjuntoContextual::class, SupportCatalogPolicy::class);
    }
}
