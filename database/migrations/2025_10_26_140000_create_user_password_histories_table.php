<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_password_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('password');
            $table->timestamps();
        });

        DB::table('users')
            ->select('id', 'password')
            ->whereNotNull('password')
            ->orderBy('id')
            ->chunkById(100, function ($users) {
                $now = now();
                $records = [];

                foreach ($users as $user) {
                    if (! $user->password) {
                        continue;
                    }

                    $records[] = [
                        'user_id' => $user->id,
                        'password' => $user->password,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if (! empty($records)) {
                    DB::table('user_password_histories')->insert($records);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_password_histories');
    }
};
