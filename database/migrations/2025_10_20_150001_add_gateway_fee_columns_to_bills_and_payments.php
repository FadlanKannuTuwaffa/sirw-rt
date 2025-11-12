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
        Schema::table('bills', function (Blueprint $table) {
            if (!Schema::hasColumn('bills', 'gateway_fee')) {
                $table->unsignedInteger('gateway_fee')->default(0)->after('amount');
            }

            if (!Schema::hasColumn('bills', 'total_amount')) {
                $table->unsignedInteger('total_amount')->nullable()->after('gateway_fee');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'fee_amount')) {
                $table->unsignedInteger('fee_amount')->default(0)->after('amount');
            }

            if (!Schema::hasColumn('payments', 'customer_total')) {
                $table->unsignedInteger('customer_total')->nullable()->after('fee_amount');
            }
        });

        // Backfill existing data
        if (Schema::hasColumn('bills', 'total_amount')) {
            DB::table('bills')
                ->whereNull('total_amount')
                ->update(['total_amount' => DB::raw('amount')]);
        }

        if (Schema::hasColumn('payments', 'customer_total')) {
            DB::table('payments')
                ->whereNull('customer_total')
                ->update(['customer_total' => DB::raw('amount')]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'customer_total')) {
                $table->dropColumn('customer_total');
            }

            if (Schema::hasColumn('payments', 'fee_amount')) {
                $table->dropColumn('fee_amount');
            }
        });

        Schema::table('bills', function (Blueprint $table) {
            if (Schema::hasColumn('bills', 'total_amount')) {
                $table->dropColumn('total_amount');
            }

            if (Schema::hasColumn('bills', 'gateway_fee')) {
                $table->dropColumn('gateway_fee');
            }
        });
    }
};
