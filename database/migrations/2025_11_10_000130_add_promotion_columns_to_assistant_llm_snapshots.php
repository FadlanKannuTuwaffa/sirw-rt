<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assistant_llm_snapshots', function (Blueprint $table) {
            if (!Schema::hasColumn('assistant_llm_snapshots', 'promotion_status')) {
                $table->string('promotion_status', 32)
                    ->default('pending')
                    ->after('metadata');
            }

            if (!Schema::hasColumn('assistant_llm_snapshots', 'promotion_attempts')) {
                $table->unsignedInteger('promotion_attempts')
                    ->default(0)
                    ->after('promotion_status');
            }

            if (!Schema::hasColumn('assistant_llm_snapshots', 'promoted_at')) {
                $table->timestamp('promoted_at')
                    ->nullable()
                    ->after('promotion_attempts');
            }

            if (!Schema::hasColumn('assistant_llm_snapshots', 'promotion_notes')) {
                $table->text('promotion_notes')
                    ->nullable()
                    ->after('promoted_at');
            }

            if (!Schema::hasColumn('assistant_llm_snapshots', 'promotion_payload')) {
                $table->json('promotion_payload')
                    ->nullable()
                    ->after('promotion_notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('assistant_llm_snapshots', function (Blueprint $table) {
            if (Schema::hasColumn('assistant_llm_snapshots', 'promotion_payload')) {
                $table->dropColumn('promotion_payload');
            }

            if (Schema::hasColumn('assistant_llm_snapshots', 'promotion_notes')) {
                $table->dropColumn('promotion_notes');
            }

            if (Schema::hasColumn('assistant_llm_snapshots', 'promoted_at')) {
                $table->dropColumn('promoted_at');
            }

            if (Schema::hasColumn('assistant_llm_snapshots', 'promotion_attempts')) {
                $table->dropColumn('promotion_attempts');
            }

            if (Schema::hasColumn('assistant_llm_snapshots', 'promotion_status')) {
                $table->dropColumn('promotion_status');
            }
        });
    }
};
