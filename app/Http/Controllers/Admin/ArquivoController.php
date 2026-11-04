<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Arquivo;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ArquivoController extends Controller
{
    public function show(Arquivo $arquivo): BinaryFileResponse
    {
        Gate::authorize('view', $arquivo);

        abort_unless($arquivo->provider === 'local', Response::HTTP_NOT_FOUND);

        $path = Storage::disk('local')->path($arquivo->storage_path);

        abort_unless(is_file($path), Response::HTTP_NOT_FOUND);

        return response()->file($path, [
            'Content-Type' => $arquivo->mime_type,
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
