<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assistant_tool_blueprints', function (Blueprint $table) {
            $table->id();
            $table->string('intent');
            $table->text('sample_failure')->nullable();
            $table->float('failure_rate')->default(0);
            $table->float('tool_usage_rate')->default(0);
            $table->unsignedInteger('total_interactions')->default(0);
            $table->enum('status', ['pending', 'in_progress', 'implemented', 'rejected'])->default('pending');
            $table->json('source_payload')->nullable();
            $table->timestamp('recommended_at')->nullable();
            $table->timestamp('implemented_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('intent');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistant_tool_blueprints');
    }
};
