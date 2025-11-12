<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assistant_interaction_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('query');
            $table->string('classification_type', 32);
            $table->float('confidence')->nullable();
            $table->json('intents')->nullable();
            $table->json('tool_calls')->nullable();
            $table->boolean('tool_success')->nullable();
            $table->string('responded_via', 32);
            $table->boolean('success')->default(true);
            $table->integer('duration_ms')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistant_interaction_logs');
    }
};
