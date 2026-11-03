<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Identificação informada pela instituição; independente do ID e da localização física.
    // O índice único também reserva os códigos de itens na lixeira, que continuam na tabela.
    public function up(): void
    {
        Schema::table('item_acervos', function (Blueprint $table): void {
            $table->string('codigo_referencia', 100)->nullable()->unique()->after('titulo');
        });
    }

    public function down(): void
    {
        Schema::table('item_acervos', function (Blueprint $table): void {
            $table->dropUnique(['codigo_referencia']);
            $table->dropColumn('codigo_referencia');
        });
    }
};
