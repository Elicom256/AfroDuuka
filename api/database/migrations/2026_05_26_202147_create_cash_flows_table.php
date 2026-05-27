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
    Schema::create('cash_flows', function (Blueprint $table) {
        $table->id();
        
        // Core transaction info
        $table->string('transaction_code')->unique();   // e.g. CF-00001, INV-00045, PO-00321
        $table->string('type');                         // 'sale', 'purchase', 'expense', 'payment_in', 'payment_out', 'refund'
        $table->decimal('amount', 15, 2);               // Positive for inflows, Negative for outflows (or use separate sign logic)
        $table->string('currency')->default('UGX');
        
        // Relationship to business
        $table->foreignId('business_id')->constrained()->onDelete('cascade');
        $table->foreignId('business_branch_id')->nullable()->constrained('business_branches');
        
        // Who is involved?
        $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
        $table->foreignId('supplier_id')->nullable()->constrained()->onDelete('set null');
        
        // Optional: Link to actual documents
        $table->foreignId('sale_id')->nullable()->constrained('sales')->onDelete('set null');
        $table->foreignId('purchase_id')->nullable()->constrained('purchases')->onDelete('set null');
        
        // Description & categorization
        $table->string('description')->nullable();
        $table->enum('category', ['product_sales', 'product_purchases', 'raw_materials', 'rent',])->nullable();         // e.g. 'product_sales', 'raw_materials', 'rent', 'utilities'
        
        // Payment details
        $table->foreignId('payment_status_id')->nullable()->constrained()->cascadeOnDelete();
        $table->string('reference')->nullable();        // receipt number, cheque number, transaction ID
        
        // Status & Dates
        $table->enum('status', ['pending', 'completed', 'cancelled'])->default('completed');
        $table->date('transaction_date')->default(now());
        
        // Audit
        $table->foreignId('created_by')->nullable()->constrained('users');
        
        $table->timestamps();
        $table->softDeletes();
        
        // Indexes for performance
        $table->index(['business_id', 'transaction_date']);
        $table->index(['type', 'status']);
        $table->index(['customer_id', 'supplier_id']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_flows');
    }
};
