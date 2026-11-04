<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contribuicoes', function (Blueprint $table): void {
            $table->id();
            $table->string('nome');
            $table->string('email');
            $table->string('titulo');
            $table->text('historia')->nullable();
            $table->string('local')->nullable();
            $table->string('periodo')->nullable();
            $table->boolean('autorizacao')->default(false);
            $table->timestamps();
        });

        Schema::create('contribuicao_arquivos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contribuicao_id')->constrained('contribuicoes')->cascadeOnDelete();
            $table->string('nome_original');
            $table->string('caminho');
            $table->string('mime_type');
            $table->unsignedBigInteger('tamanho');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contribuicao_arquivos');
        Schema::dropIfExists('contribuicoes');
    }
};
