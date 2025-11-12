<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assistant_fact_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assistant_correction_event_id')
                ->nullable()
                ->constrained('assistant_correction_events')
                ->nullOnDelete();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('org_id')->nullable()->index();
            $table->string('thread_id', 64)->nullable();
            $table->string('turn_id', 64)->nullable();
            $table->string('scope', 16)->default('user');
            $table->string('entity_type', 32);
            $table->string('field', 64);
            $table->string('fingerprint', 80)->nullable()->index();
            $table->string('status', 20)->default('pending')->index();
            $table->text('value')->nullable();
            $table->text('value_raw')->nullable();
            $table->json('match_context')->nullable();
            $table->text('source_feedback')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistant_fact_corrections');
    }
};
