<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assistant_health_checks', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('status')->default('ok');
            $table->timestamp('last_success_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistant_health_checks');
    }
};
