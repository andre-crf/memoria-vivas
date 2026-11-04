<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use App\Queries\Publico\ConsultaFotografiasPublicas;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class HomeController extends Controller
{
    public function __invoke(ConsultaFotografiasPublicas $fotografias): View
    {
        return view('public.home', [
            'fotografias' => $fotografias->recentes(5),
            'heroSlides' => $this->heroSlides(),
        ]);
    }

    private function heroSlides(): Collection
    {
        return collect(config('hero.slides', []))
            ->filter(fn (mixed $slide): bool => is_array($slide)
                && is_string($slide['imagem'] ?? null)
                && str_starts_with($slide['imagem'], 'images/')
                && ! str_contains($slide['imagem'], '..')
                && is_file(public_path($slide['imagem']))
                && is_string($slide['alt'] ?? null)
                && trim($slide['alt']) !== '')
            ->values();
    }
}
