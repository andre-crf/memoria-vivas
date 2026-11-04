<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bancos criados antes da normalização ainda podem ter pais/estado/cidade
        // como texto, embora a migration original esteja marcada como executada.
        if (! Schema::hasColumn('registro_downloads', 'pais_id')) {
            Schema::table('registro_downloads', function (Blueprint $table): void {
                $table->foreignId('pais_id')->nullable()->constrained('paises')->restrictOnDelete();
            });
        }

        if (! Schema::hasColumn('registro_downloads', 'estado_id')) {
            Schema::table('registro_downloads', function (Blueprint $table): void {
                $table->foreignId('estado_id')->nullable()->constrained('estados')->restrictOnDelete();
            });
        }

        if (! Schema::hasColumn('registro_downloads', 'municipio_id')) {
            Schema::table('registro_downloads', function (Blueprint $table): void {
                $table->foreignId('municipio_id')->nullable()->constrained('municipios')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Preserva dados e colunas existentes: não há como distinguir com
        // segurança as colunas antigas das adicionadas por esta reparação.
    }
};
