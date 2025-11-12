<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assistant_llm_snapshots', function (Blueprint $table) {
            if (!Schema::hasColumn('assistant_llm_snapshots', 'positive_feedback_count')) {
                $table->unsignedSmallInteger('positive_feedback_count')
                    ->default(0)
                    ->after('is_helpful');
            }

            if (!Schema::hasColumn('assistant_llm_snapshots', 'negative_feedback_count')) {
                $table->unsignedSmallInteger('negative_feedback_count')
                    ->default(0)
                    ->after('positive_feedback_count');
            }

            if (!Schema::hasColumn('assistant_llm_snapshots', 'auto_promote_ready')) {
                $table->boolean('auto_promote_ready')
                    ->default(false)
                    ->after('needs_review');
            }

            if (!Schema::hasColumn('assistant_llm_snapshots', 'last_feedback_at')) {
                $table->timestamp('last_feedback_at')
                    ->nullable()
                    ->after('feedback_note');
            }
        });

        if (!Schema::hasTable('assistant_llm_snapshot_reviews')) {
            Schema::create('assistant_llm_snapshot_reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('assistant_llm_snapshot_id')
                    ->constrained()
                    ->cascadeOnDelete();
                $table->foreignId('user_id')
                    ->nullable()
                    ->constrained()
                    ->nullOnDelete();
                $table->string('action', 48);
                $table->text('notes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('assistant_llm_snapshot_reviews')) {
            Schema::dropIfExists('assistant_llm_snapshot_reviews');
        }

        Schema::table('assistant_llm_snapshots', function (Blueprint $table) {
            if (Schema::hasColumn('assistant_llm_snapshots', 'last_feedback_at')) {
                $table->dropColumn('last_feedback_at');
            }
            if (Schema::hasColumn('assistant_llm_snapshots', 'auto_promote_ready')) {
                $table->dropColumn('auto_promote_ready');
            }
            if (Schema::hasColumn('assistant_llm_snapshots', 'negative_feedback_count')) {
                $table->dropColumn('negative_feedback_count');
            }
            if (Schema::hasColumn('assistant_llm_snapshots', 'positive_feedback_count')) {
                $table->dropColumn('positive_feedback_count');
            }
        });
    }
};
