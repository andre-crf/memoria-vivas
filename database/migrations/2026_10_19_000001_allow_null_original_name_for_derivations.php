<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('arquivos', function (Blueprint $table) {
            $table->string('nome_original')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Mantem compatibilidade com derivacoes existentes, que nao tem nome original.
    }
};
