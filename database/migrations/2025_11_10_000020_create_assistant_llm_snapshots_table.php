<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assistant_llm_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assistant_interaction_log_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('thread_id')->nullable();
            $table->string('intent')->nullable();
            $table->float('confidence')->nullable();
            $table->string('provider')->nullable();
            $table->string('responded_via')->default('llm');
            $table->boolean('is_fallback')->default(false);
            $table->text('content')->nullable();
            $table->json('rag_sources')->nullable();
            $table->json('tool_calls')->nullable();
            $table->boolean('is_helpful')->nullable();
            $table->boolean('needs_review')->default(false);
            $table->string('feedback_source')->nullable();
            $table->text('feedback_note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistant_llm_snapshots');
    }
};
