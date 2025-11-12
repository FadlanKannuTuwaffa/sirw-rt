<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('assistant_kb_document_weights')) {
            return;
        }

        Schema::create('assistant_kb_document_weights', function (Blueprint $table) {
            $table->id();
            $table->string('document_id')->unique();
            $table->string('title')->nullable();
            $table->unsignedInteger('helpful_count')->default(0);
            $table->unsignedInteger('unhelpful_count')->default(0);
            $table->float('weight')->default(1.0);
            $table->boolean('needs_review')->default(false);
            $table->text('last_note')->nullable();
            $table->timestamp('last_feedback_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistant_kb_document_weights');
    }
};