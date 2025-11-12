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
        Schema::create('reminders', function (Blueprint $t) {
            $t->id();
            $t->morphs('model'); // model_type & model_id (Bill/Event)
            $t->string('channel')->default('email');
            $t->timestamp('send_at')->index();
            $t->timestamp('sent_at')->nullable();
            $t->string('status')->default('scheduled'); // scheduled/sent/failed
            $t->json('payload')->nullable();
            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reminders');
    }
};
