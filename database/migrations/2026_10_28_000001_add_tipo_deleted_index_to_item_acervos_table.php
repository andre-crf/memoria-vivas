<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // As listagens administrativas (ativas e lixeira) filtram por tipo_item e deleted_at.
    public function up(): void
    {
        Schema::table('item_acervos', function (Blueprint $table): void {
            $table->index(['tipo_item', 'deleted_at'], 'item_acervos_tipo_deleted_idx');
        });
    }

    public function down(): void
    {
        Schema::table('item_acervos', function (Blueprint $table): void {
            $table->dropIndex('item_acervos_tipo_deleted_idx');
        });
    }
};
