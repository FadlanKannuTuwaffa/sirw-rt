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
        Schema::create('telegram_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('telegram_user_id')->unique();
            $table->unsignedBigInteger('telegram_chat_id')->unique();
            $table->string('username')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('language_code', 8)->nullable();
            $table->boolean('receive_notifications')->default(true);
            $table->timestamp('linked_at')->nullable();
            $table->timestamp('unlinked_at')->nullable();
            $table->timestamp('last_interaction_at')->nullable();
            $table->json('preferences')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_accounts');
    }
};
