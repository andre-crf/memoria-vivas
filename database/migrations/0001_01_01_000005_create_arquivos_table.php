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
            // Obrigatório apenas para a versão original; derivações (thumbnail,
            // medium, large) não têm nome de origem. A regra fica na aplicação.
            $table->string('nome_original')->nullable();
            $table->enum('provider', ['local', 's3', 'google_drive', 'outro'])->default('local');
            $table->string('external_file_id')->nullable();
            $table->string('storage_path');
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size');
            $table->enum('tipo_arquivo', ['imagem', 'documento', 'audio', 'video', 'outro'])->default('outro');
            // Hash do conteúdo, calculado no upload com hash_file('sha256', ...).
            // Índice comum, não único: duplicatas devem ser detectadas, não bloqueadas.
            $table->char('sha256', 64)->nullable()->index();
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
