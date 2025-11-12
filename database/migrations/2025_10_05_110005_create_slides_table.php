<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('slides', function (Blueprint $t) {
            $t->id();
            $t->string('title')->nullable();
            $t->string('subtitle')->nullable();
            $t->text('description')->nullable();
            $t->string('button_label')->nullable();
            $t->string('button_url')->nullable();
            $t->string('image_path')->nullable();
            $t->unsignedInteger('position')->default(0);
            $t->boolean('is_active')->default(true)->index();
            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slides');
    }
};