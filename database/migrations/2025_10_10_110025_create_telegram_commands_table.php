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
        Schema::create('telegram_commands', function (Blueprint $table) {
            $table->id();
            $table->string('command')->unique();
            $table->string('description');
            $table->string('type')->default('custom');
            $table->boolean('is_admin_only')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->text('response_template')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        $timestamp = now();

        DB::table('telegram_commands')->insert([
            [
                'command' => 'start',
                'description' => 'Mulai & tautkan akun (pakai LINK-XXXX)',
                'type' => 'system',
                'is_admin_only' => false,
                'is_active' => true,
                'is_system' => true,
                'response_template' => null,
                'meta' => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'command' => 'help',
                'description' => 'Bantuan & cara pakai bot',
                'type' => 'system',
                'is_admin_only' => false,
                'is_active' => true,
                'is_system' => true,
                'response_template' => "Gunakan /start LINK-XXXX untuk menautkan akun dengan kode penautan.\nPeriksa tagihan dengan /bills atau /bill <ID>.",
                'meta' => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'command' => 'link',
                'description' => 'Petunjuk menautkan akun Telegram',
                'type' => 'system',
                'is_admin_only' => false,
                'is_active' => true,
                'is_system' => true,
                'response_template' => null,
                'meta' => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'command' => 'unlink',
                'description' => 'Lepaskan tautan akun Telegram',
                'type' => 'system',
                'is_admin_only' => false,
                'is_active' => true,
                'is_system' => true,
                'response_template' => null,
                'meta' => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'command' => 'bills',
                'description' => 'Lihat daftar tagihan saya',
                'type' => 'system',
                'is_admin_only' => false,
                'is_active' => true,
                'is_system' => true,
                'response_template' => null,
                'meta' => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'command' => 'bill',
                'description' => 'Lihat detail tagihan (pakai /bill <ID>)',
                'type' => 'system',
                'is_admin_only' => false,
                'is_active' => true,
                'is_system' => true,
                'response_template' => null,
                'meta' => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'command' => 'status',
                'description' => 'Cek preferensi notifikasi (Email/Telegram)',
                'type' => 'system',
                'is_admin_only' => false,
                'is_active' => true,
                'is_system' => true,
                'response_template' => null,
                'meta' => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'command' => 'unsubscribe',
                'description' => 'Berhenti menerima reminder via Telegram',
                'type' => 'system',
                'is_admin_only' => false,
                'is_active' => true,
                'is_system' => true,
                'response_template' => null,
                'meta' => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'command' => 'contact',
                'description' => 'Info kontak pengurus (email/WA)',
                'type' => 'system',
                'is_admin_only' => false,
                'is_active' => true,
                'is_system' => true,
                'response_template' => null,
                'meta' => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'command' => 'lang',
                'description' => 'Ganti bahasa aplikasi (id/en)',
                'type' => 'system',
                'is_admin_only' => false,
                'is_active' => true,
                'is_system' => true,
                'response_template' => null,
                'meta' => json_encode(['options' => ['id', 'en']]),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'command' => 'broadcast',
                'description' => 'Kirim pengumuman ke semua pengguna',
                'type' => 'system',
                'is_admin_only' => true,
                'is_active' => true,
                'is_system' => true,
                'response_template' => null,
                'meta' => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'command' => 'stats',
                'description' => 'Statistik: jumlah user / tagihan',
                'type' => 'system',
                'is_admin_only' => true,
                'is_active' => true,
                'is_system' => true,
                'response_template' => null,
                'meta' => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_commands');
    }
};
