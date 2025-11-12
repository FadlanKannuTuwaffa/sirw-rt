<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('username')->unique()->nullable()->after('email');
            $t->string('nik', 32)->unique()->nullable()->after('username');
            $t->string('phone', 32)->nullable()->after('nik');
            $t->text('alamat')->nullable()->after('phone');
            $t->enum('role', ['admin', 'warga'])->default('warga')->after('alamat');
            $t->enum('status', ['aktif', 'pindah', 'nonaktif'])->default('aktif')->after('role');
            $t->timestamp('last_seen_at')->nullable()->after('remember_token');
            $t->string('profile_photo_path')->nullable()->after('last_seen_at');
            $t->text('notes')->nullable()->after('profile_photo_path');
            $t->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->dropUnique(['username']);
            $t->dropUnique(['nik']);
            $t->dropColumn([
                'username',
                'nik',
                'phone',
                'alamat',
                'role',
                'status',
                'last_seen_at',
                'profile_photo_path',
                'notes',
                'deleted_at',
            ]);
        });
    }
};