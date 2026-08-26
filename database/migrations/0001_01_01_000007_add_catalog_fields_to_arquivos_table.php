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
        Schema::table('arquivos', function (Blueprint $table) {
            // Arquivo não existe solto: o forceDelete do item leva junto os
            // registros técnicos. O soft delete do item não dispara o cascade.
            $table->foreignId('item_acervo_id')->constrained('item_acervos')->cascadeOnDelete();
            $table->enum('versao_arquivo', ['original', 'thumbnail', 'medium', 'large'])->default('original');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();

            $table->unique(['item_acervo_id', 'versao_arquivo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('arquivos', function (Blueprint $table) {
            $table->dropUnique(['item_acervo_id', 'versao_arquivo']);
            $table->dropConstrainedForeignId('item_acervo_id');
            $table->dropColumn(['versao_arquivo', 'width', 'height']);
        });
    }
};
