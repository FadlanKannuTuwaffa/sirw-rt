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
        Schema::create('citizen_records', function (Blueprint $t) {
            $t->id();
            $t->string('nik')->unique();
            $t->string('nama');
            $t->string('email')->nullable();
            $t->string('alamat')->nullable();
            $t->enum('status', ['available', 'claimed'])->default('available');
            $t->foreignId('claimed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('citizen_records');
    }
};
