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
        Schema::create('ledger_entries', function (Blueprint $t) {
            $t->id();
            $t->enum('category', ['kas', 'sumbangan', 'pengeluaran'])->index();
            $t->integer('amount'); // + untuk pemasukan, - untuk pengeluaran
            $t->foreignId('bill_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamp('occurred_at')->index();
            $t->string('notes')->nullable();
            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};
