<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * cash_flows.stock_transfer_id was never created.
 *
 * CashFlowService::createCashFlowForStockTransferDispatch() and its receive()
 * counterpart both write the column, and CashFlow lists it in $fillable, but the
 * original create_cash_flows_table migration stopped at sale_id, purchase_id and
 * expense_id — the three that existed when it was written. Stock transfers came
 * later and added the write without the column.
 *
 * The effect was that dispatching a stock transfer always threw
 * SQLSTATE[42703] Undefined column "stock_transfer_id", which
 * StockTransferController::dispatch() catches and reports as a 422 "message" —
 * so the failure looked like a business rule rejection rather than a broken
 * schema. The transfer stayed in draft and stock never moved.
 *
 * Sibling reference columns in this table are all nullable with set null on
 * delete, so the cash-flow row outlives the transfer it points at. This follows
 * the same shape.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_flows', function (Blueprint $table) {
            $table->foreignId('stock_transfer_id')
                ->nullable()
                ->after('purchase_id')
                ->constrained('stock_transfers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cash_flows', function (Blueprint $table) {
            $table->dropForeign(['stock_transfer_id']);
            $table->dropColumn('stock_transfer_id');
        });
    }
};
