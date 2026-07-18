<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("coupons", function (Blueprint $table) {
            $table->id();
            $table->foreignId("business_id")->constrained()->cascadeOnDelete();
            $table->foreignId("business_branch_id")->constrained()->cascadeOnDelete();
            $table->string("code")->unique();
            $table->text("description")->nullable();
            $table->string("discount_type")->default("percentage");
            $table->decimal("discount_value", 12, 2);
            $table->decimal("min_order_amount", 12, 2)->default(0);
            $table->date("valid_from");
            $table->date("valid_until");
            $table->integer("max_uses")->nullable();
            $table->integer("used_count")->default(0);
            $table->string("status")->default("active");
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("coupons");
    }
};
