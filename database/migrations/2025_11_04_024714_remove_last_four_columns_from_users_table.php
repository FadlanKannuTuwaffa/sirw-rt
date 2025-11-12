<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nik_last_four', 'phone_last_four', 'nik_encrypted', 'phone_encrypted', 'alamat_encrypted']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('nik_last_four')->nullable();
            $table->string('phone_last_four')->nullable();
            $table->text('nik_encrypted')->nullable();
            $table->text('phone_encrypted')->nullable();
            $table->text('alamat_encrypted')->nullable();
        });
    }
};
