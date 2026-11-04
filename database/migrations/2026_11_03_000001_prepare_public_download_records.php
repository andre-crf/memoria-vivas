<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registro_downloads', function (Blueprint $table): void {
            $table->foreignId('arquivo_id')->nullable()->constrained('arquivos')->nullOnDelete();
            $table->string('nome', 120)->nullable();
            $table->string('email', 255)->nullable();
            $table->text('finalidade')->nullable();
            $table->timestamp('termos_aceitos_em')->nullable();
        });

        $agora = now();
        foreach (['Pesquisa acadêmica', 'Atividade educativa', 'Uso pessoal', 'Divulgação cultural', 'Outro'] as $titulo) {
            DB::table('motivos_download')->insertOrIgnore(['titulo' => $titulo, 'ativo' => true, 'created_at' => $agora, 'updated_at' => $agora]);
        }
        foreach (['Estudante / aluno(a)', 'Pesquisador(a)', 'Educador(a)', 'Profissional de comunicação', 'Público geral', 'Outro'] as $titulo) {
            DB::table('perfis_download')->insertOrIgnore(['titulo' => $titulo, 'ativo' => true, 'created_at' => $agora, 'updated_at' => $agora]);
        }
    }

    public function down(): void
    {
        Schema::table('registro_downloads', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('arquivo_id');
            $table->dropColumn(['nome', 'email', 'finalidade', 'termos_aceitos_em']);
        });
    }
};
