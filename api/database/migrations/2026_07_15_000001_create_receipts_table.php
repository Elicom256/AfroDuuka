<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The serial behind receipt_number.
     *
     * receipt_number is UNIQUE, so it has to be handed out atomically. It used to
     * be derived from a count of the day's receipts, read in one statement and
     * written in a later one, which lost the whole sale whenever two tills read
     * the same count — and which also collided across tenants, because the count
     * was tenant-scoped while the column is not.
     *
     * A sequence is a standalone schema object, so it cannot be a column on this
     * table; nextval() is atomic, never blocks and never rolls back, which means
     * a number consumed by a sale that later fails is not reissued to the next
     * customer. See App\Services\ReceiptNumberGenerator.
     */
    private const SEQUENCE = 'receipt_number_seq';

    public function up(): void
    {
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number')->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('business_id')->constrained();
            $table->foreignId('business_branch_id')->constrained();
            $table->foreignId('sale_id')->constrained();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->decimal('change_given', 15, 2)->default(0);
            $table->string('payment_method');
            $table->enum('status', ['completed', 'refunded', 'voided'])->default('completed');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        DB::statement('CREATE SEQUENCE IF NOT EXISTS '.self::SEQUENCE);
    }

    public function down(): void
    {
        DB::statement('DROP SEQUENCE IF EXISTS '.self::SEQUENCE);

        Schema::dropIfExists('receipts');
    }
};
