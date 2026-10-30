<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // A data de adição é a ordenação padrão da listagem administrativa de fotografias.
    public function up(): void
    {
        Schema::table('item_acervos', function (Blueprint $table): void {
            $table->index('created_at', 'item_acervos_created_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('item_acervos', function (Blueprint $table): void {
            $table->dropIndex('item_acervos_created_at_idx');
        });
    }
};
