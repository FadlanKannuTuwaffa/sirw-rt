<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assistant_kb_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assistant_interaction_log_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('token')->unique();
            $table->text('question');
            $table->text('answer_excerpt')->nullable();
            $table->boolean('helpful')->nullable();
            $table->text('note')->nullable();
            $table->json('sources')->nullable();
            $table->float('confidence')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistant_kb_feedback');
    }
};
