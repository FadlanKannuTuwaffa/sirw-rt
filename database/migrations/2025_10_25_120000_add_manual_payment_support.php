<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'manual_channel')) {
                $table->string('manual_channel')->nullable()->after('gateway');
            }

            if (! Schema::hasColumn('payments', 'manual_destination')) {
                $table->json('manual_destination')->nullable()->after('manual_channel');
            }

            if (! Schema::hasColumn('payments', 'manual_proof_path')) {
                $table->string('manual_proof_path')->nullable()->after('manual_destination');
            }

            if (! Schema::hasColumn('payments', 'manual_proof_uploaded_at')) {
                $table->timestamp('manual_proof_uploaded_at')->nullable()->after('manual_proof_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'manual_proof_uploaded_at')) {
                $table->dropColumn('manual_proof_uploaded_at');
            }

            if (Schema::hasColumn('payments', 'manual_proof_path')) {
                $table->dropColumn('manual_proof_path');
            }

            if (Schema::hasColumn('payments', 'manual_destination')) {
                $table->dropColumn('manual_destination');
            }

            if (Schema::hasColumn('payments', 'manual_channel')) {
                $table->dropColumn('manual_channel');
            }
        });
    }
};

