<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('assistant_kb_feedback')) {
            return;
        }

        Schema::table('assistant_kb_feedback', function (Blueprint $table) {
            if (!Schema::hasColumn('assistant_kb_feedback', 'processed_at')) {
                $table->timestamp('processed_at')->nullable()->after('responded_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('assistant_kb_feedback')) {
            return;
        }

        Schema::table('assistant_kb_feedback', function (Blueprint $table) {
            if (Schema::hasColumn('assistant_kb_feedback', 'processed_at')) {
                $table->dropColumn('processed_at');
            }
        });
    }
};