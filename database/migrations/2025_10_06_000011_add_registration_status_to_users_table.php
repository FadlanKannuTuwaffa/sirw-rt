<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'registration_status')) {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('registration_status', ['pending', 'active'])->default('active')->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'registration_status')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('registration_status');
            });
        }
    }
};
