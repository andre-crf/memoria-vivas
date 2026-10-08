<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use App\Models\Arquivo;
use App\Queries\Publico\ConsultaFotografiasPublicas;
use App\Services\Arquivos\Contracts\ArquivoStorage;
use Illuminate\Http\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImagemPublicaController extends Controller
{
    public function __invoke(
        Arquivo $arquivo,
        ConsultaFotografiasPublicas $fotografias,
        ArquivoStorage $storage,
    ): BinaryFileResponse {
        $fotografia = $fotografias->porIdentificador($arquivo->item_acervo_id);
        $imagemAutorizada = $fotografia?->arquivos->contains(
            fn (Arquivo $derivacao): bool => $derivacao->is($arquivo),
        );

        abort_unless($imagemAutorizada, Response::HTTP_NOT_FOUND);

        try {
            $path = $storage->absolutePath($arquivo->provider, $arquivo->storage_path);
        } catch (RuntimeException) {
            abort(Response::HTTP_NOT_FOUND);
        }

        abort_unless(is_file($path) && is_readable($path), Response::HTTP_NOT_FOUND);

        $response = response()->file($path, [
            'Content-Type' => $arquivo->mime_type,
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
        ]);

        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-store');

        return $response;
    }
}
