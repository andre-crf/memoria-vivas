<?php

use App\Http\Controllers\Admin\AssuntoController;
use App\Http\Controllers\Admin\AutorController;
use App\Http\Controllers\Admin\CategoriaController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FotografiaController;
use App\Http\Controllers\Admin\PalavraChaveController;
use App\Http\Controllers\Admin\PessoaController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function (): RedirectResponse {
    return Auth::check()
        ? redirect()->route('admin.dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'admin.access'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {

        Route::get('/', DashboardController::class)->name('dashboard');

        Route::prefix('fotografias')
            ->name('fotografias.')
            ->group(function (): void {
                Route::get('/create', [FotografiaController::class, 'create'])->name('create');
                Route::post('/', [FotografiaController::class, 'store'])->name('store');
                Route::get('/', [FotografiaController::class, 'index'])->name('index');
                Route::get('/lixeira', [FotografiaController::class, 'trashed'])->name('trashed');
                Route::get('/{fotografia}/edit', [FotografiaController::class, 'edit'])->name('edit');
                Route::put('/{fotografia}', [FotografiaController::class, 'update'])->name('update');
                Route::patch('/{fotografia}/restore', [FotografiaController::class, 'restore'])->name('restore');
                Route::delete('/{fotografia}/force', [FotografiaController::class, 'forceDestroy'])->name('force-destroy');
                Route::get('/{fotografia}', [FotografiaController::class, 'show'])->name('show');
                Route::delete('/{fotografia}', [FotografiaController::class, 'destroy'])->name('destroy');
            });

        Route::prefix('categorias')
            ->name('categorias.')
            ->group(function (): void {
                Route::get('/', [CategoriaController::class, 'index'])->name('index');
                Route::get('/create', [CategoriaController::class, 'create'])->name('create');
                Route::post('/', [CategoriaController::class, 'store'])->name('store');
                Route::get('/{categoria}/edit', [CategoriaController::class, 'edit'])->name('edit');
                Route::put('/{categoria}', [CategoriaController::class, 'update'])->name('update');
                Route::delete('/{categoria}', [CategoriaController::class, 'destroy'])->name('destroy');
            });

        Route::prefix('assuntos')
            ->name('assuntos.')
            ->group(function (): void {
                Route::get('/', [AssuntoController::class, 'index'])->name('index');
                Route::get('/create', [AssuntoController::class, 'create'])->name('create');
                Route::post('/', [AssuntoController::class, 'store'])->name('store');
                Route::get('/{assunto}/edit', [AssuntoController::class, 'edit'])->name('edit');
                Route::put('/{assunto}', [AssuntoController::class, 'update'])->name('update');
                Route::delete('/{assunto}', [AssuntoController::class, 'destroy'])->name('destroy');
            });

        Route::prefix('palavras-chave')
            ->name('palavras-chave.')
            ->group(function (): void {
                Route::get('/', [PalavraChaveController::class, 'index'])->name('index');
                Route::get('/create', [PalavraChaveController::class, 'create'])->name('create');
                Route::post('/', [PalavraChaveController::class, 'store'])->name('store');
                Route::get('/{palavraChave}/edit', [PalavraChaveController::class, 'edit'])->name('edit');
                Route::put('/{palavraChave}', [PalavraChaveController::class, 'update'])->name('update');
                Route::delete('/{palavraChave}', [PalavraChaveController::class, 'destroy'])->name('destroy');
            });

        Route::prefix('autores')
            ->name('autores.')
            ->group(function (): void {
                Route::get('/', [AutorController::class, 'index'])->name('index');
                Route::get('/create', [AutorController::class, 'create'])->name('create');
                Route::post('/', [AutorController::class, 'store'])->name('store');
                Route::get('/{autor}/edit', [AutorController::class, 'edit'])->name('edit');
                Route::put('/{autor}', [AutorController::class, 'update'])->name('update');
                Route::delete('/{autor}', [AutorController::class, 'destroy'])->name('destroy');
            });

        Route::prefix('pessoas')
            ->name('pessoas.')
            ->group(function (): void {
                Route::get('/', [PessoaController::class, 'index'])->name('index');
                Route::get('/create', [PessoaController::class, 'create'])->name('create');
                Route::post('/', [PessoaController::class, 'store'])->name('store');
                Route::get('/{pessoa}/edit', [PessoaController::class, 'edit'])->name('edit');
                Route::put('/{pessoa}', [PessoaController::class, 'update'])->name('update');
                Route::delete('/{pessoa}', [PessoaController::class, 'destroy'])->name('destroy');
            });
    });
