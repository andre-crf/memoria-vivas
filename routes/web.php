<?php

use App\Http\Controllers\Admin\CategoriaController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FotografiaController;
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
    });
