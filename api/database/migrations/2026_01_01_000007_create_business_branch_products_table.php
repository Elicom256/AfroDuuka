<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('business_branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->string('name');
            $table->string('sku')->nullable();
            $table->string('barcode')->nullable();
            $table->integer('quantity')->default(0);
            $table->decimal('cost_price', 12, 2)->default(0);
            $table->decimal('selling_price', 12, 2)->default(0);
            $table->boolean('is_tax_inclusive')->default(false);
            $table->foreignId('tax_category_id')->nullable()->constrained('tax_categories')->nullOnDelete();
            $table->integer('reorder_level')->default(0);

            // Stock alert state, so a re-check at an unchanged quantity stays silent.
            // A separate product_branch_states pivot is NOT needed: business_branch_id is
            // NOT NULL, so a product row is already per-branch and quantity/reorder_level
            // are inherently branch-scoped.
            //   ok                  -> qty above reorder_level, alertable
            //   low_stock_fired     -> crossed below; do not re-fire
            //   out_of_stock_fired  -> reached zero
            //   suppressed          -> no active recipient for the inventory category
            $table->string('alert_state')->default('ok');
            // Incremented on re-arm so the next crossing yields a fresh dedupe key.
            $table->unsignedInteger('alert_episode')->default(0);

            $table->text('description')->nullable();
            $table->string('emoji')->nullable()->after('description');
            $table->enum('status', ['active', 'inactive', 'damaged', 'out_of_stock', 'discontinued'])->default('active');
            $table->date('expiry_date')->nullable()->after('status');

            $table->timestamp('last_sold_at')->nullable()->index();
            $table->timestamps();
            $table->unique(['business_branch_id', 'name']);
            $table->index(['alert_state']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
