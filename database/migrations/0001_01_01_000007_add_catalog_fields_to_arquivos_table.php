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
            $table->foreignId('item_acervo_id')->nullable()->constrained('item_acervos')->nullOnDelete();
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
