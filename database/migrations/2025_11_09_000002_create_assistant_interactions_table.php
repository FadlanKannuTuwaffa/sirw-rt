<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('assistant_interactions')) {
            return;
        }

        Schema::create('assistant_interactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('thread_id', 64)->nullable();
            $table->text('message')->nullable();
            $table->string('intent', 64)->nullable();
            $table->text('response')->nullable();
            $table->float('confidence')->nullable();
            $table->string('method', 32)->default('dummy');
            $table->boolean('was_helpful')->nullable();
            $table->text('feedback_reason')->nullable();
            $table->timestamp('feedback_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'intent']);
            $table->index('thread_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistant_interactions');
    }
};
