<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_acervos', function (Blueprint $table): void {
            $table->text('localizacao_fisica')->nullable()->after('local_epoca');
        });
    }

    public function down(): void
    {
        Schema::table('item_acervos', function (Blueprint $table): void {
            $table->dropColumn('localizacao_fisica');
        });
    }
};
