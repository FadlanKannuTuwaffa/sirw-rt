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
        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->string('method', 32)->nullable()->after('payment_id');
            $table->string('status', 16)->default('paid')->after('method');
            $table->string('fund_source', 64)->nullable()->after('status');
            $table->string('fund_destination', 64)->nullable()->after('fund_source');
            $table->string('fund_reference', 160)->nullable()->after('fund_destination');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->dropColumn([
                'method',
                'status',
                'fund_source',
                'fund_destination',
                'fund_reference',
            ]);
        });
    }
};
