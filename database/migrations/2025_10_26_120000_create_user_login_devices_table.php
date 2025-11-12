<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_login_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('device_fingerprint', 128);
            $table->string('device_label')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->boolean('is_trusted')->default(false);
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('last_alerted_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'device_fingerprint']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_login_devices');
    }
};
