<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Estado da geração das versões otimizadas, gravado só no registro do original.
    // Nos registros existentes, só o original que não é imagem tem estado certo (nada a
    // gerar); os de imagem ficam sem estado até a próxima regeneração ou substituição.
    public function up(): void
    {
        Schema::table('arquivos', function (Blueprint $table): void {
            $table->string('derivacoes_status', 20)->nullable()->after('height');
            $table->timestamp('derivacoes_atualizadas_em')->nullable()->after('derivacoes_status');
        });

        DB::table('arquivos')
            ->where('versao_arquivo', 'original')
            ->where('mime_type', 'not like', 'image/%')
            ->update(['derivacoes_status' => 'nao_aplicavel']);
    }

    public function down(): void
    {
        Schema::table('arquivos', function (Blueprint $table): void {
            $table->dropColumn(['derivacoes_status', 'derivacoes_atualizadas_em']);
        });
    }
};
