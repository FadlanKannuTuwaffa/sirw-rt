<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE events MODIFY start_at DATETIME NOT NULL");
        DB::statement("ALTER TABLE events MODIFY end_at DATETIME NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE events MODIFY start_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        DB::statement("ALTER TABLE events MODIFY end_at TIMESTAMP NULL DEFAULT NULL");
    }
};
