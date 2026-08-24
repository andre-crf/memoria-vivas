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
        Schema::create('arquivos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_acervo_id')->constrained('item_acervos')->cascadeOnDelete();
            $table->string('nome_original');
            $table->enum('provider', ['local', 'externo'])->default('local');
            $table->string('external_file_id')->nullable();
            $table->string('storage_path');
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size');
            $table->enum('tipo_arquivo', ['imagem', 'documento', 'audio', 'video', 'outro'])->default('outro');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arquivos');
    }
};
