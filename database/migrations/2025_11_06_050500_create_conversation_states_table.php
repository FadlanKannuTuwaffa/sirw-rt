<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('conversation_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('thread_id', 64);
            $table->string('owner_hash', 64)->unique();
            $table->json('state')->nullable();
            $table->timestamps();

            $table->index('thread_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_states');
    }
};
