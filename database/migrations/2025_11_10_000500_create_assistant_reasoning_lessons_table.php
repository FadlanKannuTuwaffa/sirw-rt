<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assistant_reasoning_lessons', function (Blueprint $table) {
            $table->id();
            $table->string('intent');
            $table->string('title');
            $table->json('steps');
            $table->enum('status', ['active', 'draft', 'archived'])->default('active');
            $table->unsignedInteger('priority')->default(0);
            $table->string('source')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['intent', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistant_reasoning_lessons');
    }
};
