<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assistant_interactions', function (Blueprint $table) {
            if (!Schema::hasColumn('assistant_interactions', 'assistant_interaction_log_id')) {
                $table->foreignId('assistant_interaction_log_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('assistant_interaction_logs')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('assistant_interactions', function (Blueprint $table) {
            if (Schema::hasColumn('assistant_interactions', 'assistant_interaction_log_id')) {
                $table->dropConstrainedForeignId('assistant_interaction_log_id');
            }
        });
    }
};
