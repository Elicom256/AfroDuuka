<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("purchase_orders", function (Blueprint $table) {
            $table->id();
            $table->foreignId("business_id")->constrained()->cascadeOnDelete();
            $table->foreignId("business_branch_id")->constrained()->cascadeOnDelete();
            $table->foreignId("user_id")->nullable()->constrained("users")->nullOnDelete();
            $table->foreignId("supplier_id")->nullable()->constrained()->nullOnDelete();
            $table->string("order_number")->unique();
            $table->decimal("total_amount", 12, 2)->default(0);
            $table->enum("status", ["draft", "pending", "approved", "ordered", "partially_received", "received", "cancelled"])->default("draft");
            $table->date("order_date")->nullable();
            $table->date("expected_delivery_date")->nullable();
            $table->foreignId("approved_by")->nullable()->constrained("users")->nullOnDelete();
            $table->timestamp("received_at")->nullable();
            $table->foreignId("received_by")->nullable()->constrained("users")->nullOnDelete();
            $table->text("notes")->nullable();
            $table->timestamps();
        });

        Schema::create("purchase_order_items", function (Blueprint $table) {
            $table->id();
            $table->foreignId("purchase_order_id")->constrained()->cascadeOnDelete();
            $table->foreignId("product_id")->constrained()->cascadeOnDelete();
            $table->integer("quantity");
            $table->integer("received_quantity")->default(0);
            $table->decimal("unit_price", 12, 2);
            $table->decimal("subtotal", 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("purchase_order_items");
        Schema::dropIfExists("purchase_orders");
    }
};
