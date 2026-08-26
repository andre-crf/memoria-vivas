<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('paises', function (Blueprint $table) {
            $table->id();
            $table->char('codigo', 2)->unique(); // ISO 3166-1 alpha-2: BR, AR, PT.
            $table->string('nome');
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        // Estados e municípios cobrem apenas o Brasil: para outros países o
        // registro guarda somente o país.
        Schema::create('estados', function (Blueprint $table) {
            $table->id();
            $table->char('codigo_ibge', 2)->unique();
            $table->char('sigla', 2)->unique();
            $table->string('nome');
            $table->timestamps();
        });

        Schema::create('municipios', function (Blueprint $table) {
            $table->id();
            $table->char('codigo_ibge', 7)->unique();
            $table->foreignId('estado_id')->constrained('estados')->restrictOnDelete();
            $table->string('nome');
            $table->timestamps();

            $table->index(['estado_id', 'nome']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('municipios');
        Schema::dropIfExists('estados');
        Schema::dropIfExists('paises');
    }
};
