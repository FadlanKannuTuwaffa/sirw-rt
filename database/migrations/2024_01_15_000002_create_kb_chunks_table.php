<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kb_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('kb_articles')->onDelete('cascade');
            $table->text('chunk_text');
            $table->json('embedding')->nullable();
            $table->timestamps();
            
            $table->index('article_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kb_chunks');
    }
};
