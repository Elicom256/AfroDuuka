<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("products", function (Blueprint $table) {
            $table->id();

            $table->foreignId("business_branch_id")->constrained()->cascadeOnDelete();
            $table->foreignId("product_category_id")->nullable()->constrained("product_categories")->nullOnDelete();
            $table->string("name");
            $table->string("sku")->nullable();
            $table->string("barcode")->nullable();
            $table->integer("quantity")->default(0);
            $table->decimal("cost_price", 12, 2)->default(0);
            $table->decimal("price", 12, 2)->default(0);
            $table->decimal("markup_percentage", 5, 2)->default(0)->after("cost_price");
            $table->integer("reorder_level")->default(0);
            $table->text("description")->nullable();
            $table->string("emoji")->nullable()->after("description");
            $table->enum("status", ["active", "inactive", "damaged", "out_of_stock", "discontinued"])->default("active");
            $table->date("expiry_date")->nullable()->after("status");

            $table->timestamp("last_sold_at")->nullable()->index();
            $table->timestamps();
            $table->unique(["business_branch_id", "name"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("products");
    }
};
