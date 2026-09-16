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
        if (! Schema::hasTable('arquivos') || Schema::hasColumn('arquivos', 'sha256')) {
            return;
        }

        Schema::table('arquivos', function (Blueprint $table): void {
            $table->char('sha256', 64)->nullable()->index()->after('tipo_arquivo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally left blank: fresh databases already define this column
        // in the base arquivos migration, so rolling this compatibility
        // migration back must not remove it.
    }
};
