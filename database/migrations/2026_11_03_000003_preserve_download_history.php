<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // O histórico de downloads sobrevive à regeneração das versões e à exclusão
    // definitiva da fotografia: guarda a versão entregue e a identificação mínima do
    // item, e as referências passam a ficar nulas em vez de apagar o registro.
    public function up(): void
    {
        Schema::table('registro_downloads', function (Blueprint $table): void {
            $table->string('versao_arquivo', 20)->nullable()->after('arquivo_id');
            $table->string('titulo_item')->nullable()->after('item_acervo_id');
            $table->string('codigo_referencia_item', 100)->nullable()->after('titulo_item');
        });

        DB::table('registro_downloads')->whereNotNull('arquivo_id')->update([
            'versao_arquivo' => DB::raw('(SELECT arquivos.versao_arquivo FROM arquivos WHERE arquivos.id = registro_downloads.arquivo_id)'),
        ]);
        DB::table('registro_downloads')->update([
            'titulo_item' => DB::raw('(SELECT item_acervos.titulo FROM item_acervos WHERE item_acervos.id = registro_downloads.item_acervo_id)'),
            'codigo_referencia_item' => DB::raw('(SELECT item_acervos.codigo_referencia FROM item_acervos WHERE item_acervos.id = registro_downloads.item_acervo_id)'),
        ]);

        Schema::table('registro_downloads', function (Blueprint $table): void {
            $table->dropForeign(['item_acervo_id']);
            $table->dropForeign(['pais_id']);
        });

        Schema::table('registro_downloads', function (Blueprint $table): void {
            $table->unsignedBigInteger('item_acervo_id')->nullable()->change();
            $table->unsignedBigInteger('pais_id')->nullable()->change();
        });

        Schema::table('registro_downloads', function (Blueprint $table): void {
            $table->foreign('item_acervo_id')->references('id')->on('item_acervos')->nullOnDelete();
            $table->foreign('pais_id')->references('id')->on('paises')->restrictOnDelete();
        });

        // A ausência de país passa a ser null: remove o país fictício que uma versão
        // anterior do módulo de downloads inseria na tabela sincronizada com o IBGE.
        $naoInformado = DB::table('paises')->where('codigo', 'ZZ')->where('nome', 'Não informado')->value('id');

        if ($naoInformado !== null) {
            DB::table('registro_downloads')->where('pais_id', $naoInformado)->update(['pais_id' => null]);
            DB::table('paises')->where('id', $naoInformado)->delete();
        }
    }

    public function down(): void
    {
        // Preserva os registros: as colunas continuam aceitando null, porque pode haver
        // downloads de fotografias já excluídas ou sem país identificado.
        Schema::table('registro_downloads', function (Blueprint $table): void {
            $table->dropForeign(['item_acervo_id']);
        });

        Schema::table('registro_downloads', function (Blueprint $table): void {
            $table->foreign('item_acervo_id')->references('id')->on('item_acervos')->cascadeOnDelete();
            $table->dropColumn(['versao_arquivo', 'titulo_item', 'codigo_referencia_item']);
        });
    }
};
