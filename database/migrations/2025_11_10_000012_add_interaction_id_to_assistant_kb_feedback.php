<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assistant_kb_feedback', function (Blueprint $table) {
            if (!Schema::hasColumn('assistant_kb_feedback', 'assistant_interaction_id')) {
                $table->foreignId('assistant_interaction_id')
                    ->nullable()
                    ->after('assistant_interaction_log_id')
                    ->constrained('assistant_interactions')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('assistant_kb_feedback', function (Blueprint $table) {
            if (Schema::hasColumn('assistant_kb_feedback', 'assistant_interaction_id')) {
                $table->dropConstrainedForeignId('assistant_interaction_id');
            }
        });
    }
};
