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
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'nik_encrypted')) {
                $table->text('nik_encrypted')->nullable()->after('username');
            }

            if (! Schema::hasColumn('users', 'nik_last_four')) {
                $table->string('nik_last_four', 4)->nullable()->after('nik_hash');
            }

            if (! Schema::hasColumn('users', 'phone_encrypted')) {
                $afterColumn = Schema::hasColumn('users', 'nik_last_four') ? 'nik_last_four' : 'nik_hash';
                $table->text('phone_encrypted')->nullable()->after($afterColumn);
            }

            if (! Schema::hasColumn('users', 'phone_last_four')) {
                $table->string('phone_last_four', 4)->nullable()->after('phone_hash');
            }

            if (! Schema::hasColumn('users', 'alamat_encrypted')) {
                $afterColumn = Schema::hasColumn('users', 'phone_last_four') ? 'phone_last_four' : 'phone_hash';
                $table->text('alamat_encrypted')->nullable()->after($afterColumn);
            }
        });

        if (Schema::hasColumn('users', 'nik_last_four') && ! Schema::hasIndex('users', 'users_nik_last_four_index')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index('nik_last_four');
            });
        }

        if (Schema::hasColumn('users', 'phone_last_four') && ! Schema::hasIndex('users', 'users_phone_last_four_index')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index('phone_last_four');
            });
        }

        $hasNikEncrypted = Schema::hasColumn('users', 'nik_encrypted');
        $hasNikLastFour = Schema::hasColumn('users', 'nik_last_four');
        $hasAlamatEncrypted = Schema::hasColumn('users', 'alamat_encrypted');

        if ($hasNikEncrypted || $hasNikLastFour || $hasAlamatEncrypted) {
            $records = DB::table('citizen_records')
                ->select('nik', 'alamat')
                ->whereNotNull('nik')
                ->get()
                ->reduce(function ($carry, $item) {
                    $hash = SensitiveData::hash($item->nik);
                    $carry[$hash] = [
                        'nik' => $item->nik,
                        'alamat' => $item->alamat,
                    ];

                    return $carry;
                }, []);

            if ($records !== []) {
                DB::table('users')
                    ->select('id', 'nik_hash')
                    ->orderBy('id')
                    ->chunkById(100, function ($users) use ($records, $hasNikEncrypted, $hasNikLastFour, $hasAlamatEncrypted) {
                        foreach ($users as $user) {
                            if (! $user->nik_hash) {
                                continue;
                            }

                            $record = $records[$user->nik_hash] ?? null;

                            if (! $record) {
                                continue;
                            }

                            $updates = [];

                            if ($hasNikEncrypted) {
                                $updates['nik_encrypted'] = Crypt::encryptString($record['nik']);
                            }

                            if ($hasNikLastFour) {
                                $updates['nik_last_four'] = substr($record['nik'], -4);
                            }

                            if ($hasAlamatEncrypted && $record['alamat'] !== null && trim((string) $record['alamat']) !== '') {
                                $updates['alamat_encrypted'] = Crypt::encryptString(trim((string) $record['alamat']));
                            }

                            if ($updates !== []) {
                                DB::table('users')
                                    ->where('id', $user->id)
                                    ->update($updates);
                            }
                        }
                    });
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'alamat_encrypted')) {
                $table->dropColumn('alamat_encrypted');
            }

            if (Schema::hasColumn('users', 'phone_last_four')) {
                if (Schema::hasIndex('users', 'users_phone_last_four_index')) {
                    $table->dropIndex(['phone_last_four']);
                }
                $table->dropColumn('phone_last_four');
            }

            if (Schema::hasColumn('users', 'phone_encrypted')) {
                $table->dropColumn('phone_encrypted');
            }

            if (Schema::hasColumn('users', 'nik_last_four')) {
                if (Schema::hasIndex('users', 'users_nik_last_four_index')) {
                    $table->dropIndex(['nik_last_four']);
                }
                $table->dropColumn('nik_last_four');
            }

            if (Schema::hasColumn('users', 'nik_encrypted')) {
                $table->dropColumn('nik_encrypted');
            }
        });
    }
};
