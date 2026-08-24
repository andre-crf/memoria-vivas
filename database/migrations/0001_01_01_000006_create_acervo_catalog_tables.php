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
        Schema::create('autores', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->enum('tipo', ['pessoa', 'instituicao'])->default('pessoa');
            $table->text('observacao')->nullable();
            $table->timestamps();
        });

        Schema::create('item_acervos', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo_item', ['fotografia', 'documento', 'artigo', 'jornal', 'outro'])->default('fotografia');
            $table->string('titulo');
            $table->text('legenda')->nullable();
            $table->unsignedTinyInteger('dia')->nullable();
            $table->unsignedTinyInteger('mes')->nullable();
            $table->unsignedSmallInteger('ano')->nullable();
            $table->string('decada')->nullable();
            $table->enum('tipo_data', ['data_exata', 'mes_ano', 'ano', 'decada', 'desconhecida'])
                ->default('desconhecida');
            $table->string('local_atual')->nullable();
            $table->string('local_epoca')->nullable();
            $table->string('evento')->nullable();
            $table->string('cedente')->nullable();
            $table->enum('estado_conservacao', ['excelente', 'bom', 'regular', 'ruim', 'critico', 'desconhecido'])
                ->default('desconhecido');
            $table->enum('status', ['rascunho', 'em_revisao', 'publicado', 'arquivado'])
                ->default('rascunho');
            $table->foreignId('autor_id')->nullable()->constrained('autores')->nullOnDelete();
            $table->enum('visibilidade', ['publico', 'restrito'])->default('restrito');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'visibilidade']);
            $table->index(['ano', 'mes', 'dia']);
        });

        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->string('titulo')->unique();
            $table->text('descricao')->nullable();
            $table->timestamps();
        });

        Schema::create('assuntos', function (Blueprint $table) {
            $table->id();
            $table->string('titulo')->unique();
            $table->text('descricao')->nullable();
            $table->timestamps();
        });

        Schema::create('palavras_chave', function (Blueprint $table) {
            $table->id();
            $table->string('termo')->unique();
            $table->timestamps();
        });

        Schema::create('pessoas', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->text('observacao')->nullable();
            $table->timestamps();
        });

        Schema::create('colecoes', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('descricao')->nullable();
            $table->string('imagem_capa')->nullable();
            $table->enum('status', ['rascunho', 'publicada', 'arquivada'])->default('rascunho');
            $table->timestamps();
        });

        Schema::create('conjuntos_contextuais', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('descricao')->nullable();
            $table->timestamps();
        });

        Schema::create('motivos_download', function (Blueprint $table) {
            $table->id();
            $table->string('titulo')->unique();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('perfis_download', function (Blueprint $table) {
            $table->id();
            $table->string('titulo')->unique();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('categoria_item_acervo', function (Blueprint $table) {
            $table->foreignId('categoria_id')->constrained('categorias')->cascadeOnDelete();
            $table->foreignId('item_acervo_id')->constrained('item_acervos')->cascadeOnDelete();

            $table->primary(['categoria_id', 'item_acervo_id']);
        });

        Schema::create('assunto_item_acervo', function (Blueprint $table) {
            $table->foreignId('assunto_id')->constrained('assuntos')->cascadeOnDelete();
            $table->foreignId('item_acervo_id')->constrained('item_acervos')->cascadeOnDelete();

            $table->primary(['assunto_id', 'item_acervo_id']);
        });

        Schema::create('item_acervo_palavra_chave', function (Blueprint $table) {
            $table->foreignId('item_acervo_id')->constrained('item_acervos')->cascadeOnDelete();
            $table->foreignId('palavra_chave_id')->constrained('palavras_chave')->cascadeOnDelete();

            $table->primary(['item_acervo_id', 'palavra_chave_id']);
        });

        Schema::create('item_acervo_pessoa', function (Blueprint $table) {
            $table->foreignId('item_acervo_id')->constrained('item_acervos')->cascadeOnDelete();
            $table->foreignId('pessoa_id')->constrained('pessoas')->cascadeOnDelete();

            $table->primary(['item_acervo_id', 'pessoa_id']);
        });

        Schema::create('colecao_item_acervo', function (Blueprint $table) {
            $table->foreignId('colecao_id')->constrained('colecoes')->cascadeOnDelete();
            $table->foreignId('item_acervo_id')->constrained('item_acervos')->cascadeOnDelete();

            $table->primary(['colecao_id', 'item_acervo_id']);
        });

        Schema::create('conjunto_contextual_item_acervo', function (Blueprint $table) {
            $table->foreignId('conjunto_contextual_id')->constrained('conjuntos_contextuais')->cascadeOnDelete();
            $table->foreignId('item_acervo_id')->constrained('item_acervos')->cascadeOnDelete();
            $table->unsignedInteger('ordem')->nullable();

            $table->primary(['conjunto_contextual_id', 'item_acervo_id']);
        });

        Schema::create('registro_downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_acervo_id')->constrained('item_acervos')->cascadeOnDelete();
            $table->foreignId('motivo_download_id')->constrained('motivos_download')->restrictOnDelete();
            $table->foreignId('perfil_download_id')->nullable()->constrained('perfis_download')->nullOnDelete();
            $table->string('pais')->nullable();
            $table->string('estado')->nullable();
            $table->string('cidade')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registro_downloads');
        Schema::dropIfExists('conjunto_contextual_item_acervo');
        Schema::dropIfExists('colecao_item_acervo');
        Schema::dropIfExists('item_acervo_pessoa');
        Schema::dropIfExists('item_acervo_palavra_chave');
        Schema::dropIfExists('assunto_item_acervo');
        Schema::dropIfExists('categoria_item_acervo');
        Schema::dropIfExists('conjuntos_contextuais');
        Schema::dropIfExists('perfis_download');
        Schema::dropIfExists('motivos_download');
        Schema::dropIfExists('colecoes');
        Schema::dropIfExists('pessoas');
        Schema::dropIfExists('palavras_chave');
        Schema::dropIfExists('assuntos');
        Schema::dropIfExists('categorias');
        Schema::dropIfExists('item_acervos');
        Schema::dropIfExists('autores');
    }
};
