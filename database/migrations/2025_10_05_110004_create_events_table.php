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
        Schema::create('events', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->text('description')->nullable();
            $t->string('location')->nullable();
            $t->timestamp('start_at');
            $t->timestamp('end_at')->nullable();
            $t->boolean('is_all_day')->default(false);
            $t->boolean('is_public')->default(true);
            $t->string('status')->default('scheduled')->index();
            $t->json('reminder_offsets')->nullable();
            $t->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};