<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('actor_name')->nullable();
            $table->string('actor_role', 50)->nullable();
            $table->string('action', 100);
            $table->string('subject_type', 100);
            $table->string('subject_id', 191);
            $table->string('subject_label')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('metadata')->nullable();
            $table->uuid('request_id');
            $table->uuid('correlation_id');
            $table->string('source', 32);
            $table->timestamp('occurred_at');

            $table->index(
                ['subject_type', 'subject_id', 'occurred_at'],
                'audit_subject_occurred_idx',
            );
            $table->index(
                ['actor_user_id', 'occurred_at'],
                'audit_actor_occurred_idx',
            );
            $table->index(['action', 'occurred_at'], 'audit_action_occurred_idx');
            $table->index('request_id');
            $table->index('correlation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
    }
};
