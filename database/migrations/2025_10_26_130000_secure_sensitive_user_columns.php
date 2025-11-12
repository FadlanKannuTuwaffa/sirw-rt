<?php

use App\Support\SensitiveData;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->text('nik_encrypted')->nullable()->after('username');
                $table->string('nik_hash', 64)->nullable()->after('nik_encrypted');
                $table->string('nik_last_four', 4)->nullable()->after('nik_hash');
                $table->text('phone_encrypted')->nullable()->after('nik_last_four');
                $table->string('phone_hash', 64)->nullable()->after('phone_encrypted');
                $table->string('phone_last_four', 4)->nullable()->after('phone_hash');
                $table->text('alamat_encrypted')->nullable()->after('phone_last_four');
            });

            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->text('nik_encrypted')->nullable()->after('username');
            $table->string('nik_hash', 64)->nullable()->after('nik_encrypted');
            $table->string('nik_last_four', 4)->nullable()->after('nik_hash');
            $table->text('phone_encrypted')->nullable()->after('nik_last_four');
            $table->string('phone_hash', 64)->nullable()->after('phone_encrypted');
            $table->string('phone_last_four', 4)->nullable()->after('phone_hash');
            $table->text('alamat_encrypted')->nullable()->after('phone_last_four');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['nik']);
        });

        DB::table('users')
            ->select(['id', 'nik', 'phone', 'alamat'])
            ->orderBy('id')
            ->chunkById(100, function ($rows) {
                foreach ($rows as $row) {
                    $nik = SensitiveData::normalizeDigits($row->nik);
                    $phone = SensitiveData::normalizeDigits($row->phone);
                    $alamat = $row->alamat ? trim((string) $row->alamat) : null;

                    DB::table('users')
                        ->where('id', $row->id)
                        ->update([
                            'nik_encrypted' => $nik ? Crypt::encryptString($nik) : null,
                            'nik_hash' => $nik ? SensitiveData::hash($nik) : null,
                            'nik_last_four' => $nik ? substr($nik, -4) : null,
                            'phone_encrypted' => $phone ? Crypt::encryptString($phone) : null,
                            'phone_hash' => $phone ? SensitiveData::hash($phone) : null,
                            'phone_last_four' => $phone ? substr($phone, -4) : null,
                            'alamat_encrypted' => $alamat ? Crypt::encryptString($alamat) : null,
                        ]);
                }
            });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nik', 'phone', 'alamat']);
            $table->unique('nik_hash');
            $table->index('nik_last_four');
            $table->index('phone_hash');
            $table->index('phone_last_four');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn([
                    'nik_encrypted',
                    'nik_hash',
                    'nik_last_four',
                    'phone_encrypted',
                    'phone_hash',
                    'phone_last_four',
                    'alamat_encrypted',
                ]);
            });

            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('nik', 32)->nullable()->after('username');
            $table->string('phone', 32)->nullable()->after('nik');
            $table->text('alamat')->nullable()->after('phone');
        });

        DB::table('users')
            ->select(['id', 'nik_encrypted', 'phone_encrypted', 'alamat_encrypted'])
            ->orderBy('id')
            ->chunkById(100, function ($rows) {
                foreach ($rows as $row) {
                    $nik = $row->nik_encrypted ? Crypt::decryptString($row->nik_encrypted) : null;
                    $phone = $row->phone_encrypted ? Crypt::decryptString($row->phone_encrypted) : null;
                    $alamat = $row->alamat_encrypted ? Crypt::decryptString($row->alamat_encrypted) : null;

                    DB::table('users')
                        ->where('id', $row->id)
                        ->update([
                            'nik' => $nik,
                            'phone' => $phone,
                            'alamat' => $alamat,
                        ]);
                }
            });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['nik_hash']);
            $table->dropIndex(['nik_last_four']);
            $table->dropIndex(['phone_hash']);
            $table->dropIndex(['phone_last_four']);
            $table->dropColumn([
                'nik_encrypted',
                'nik_hash',
                'nik_last_four',
                'phone_encrypted',
                'phone_hash',
                'phone_last_four',
                'alamat_encrypted',
            ]);
            $table->unique('nik');
        });
    }
};
