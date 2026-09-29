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
        Schema::table('reports', function (Blueprint $table) {
            $table->foreignId('business_id')->constrained()->cascadeOnDelete()->after('id');
            $table->string('name')->after('business_id');
            $table->string('type')->after('name');
            $table->text('description')->nullable()->after('type');
            $table->json('parameters')->nullable()->after('description');
            $table->string('schedule')->nullable()->after('parameters');
            $table->boolean('is_active')->default(true)->after('schedule');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            //
        });
    }
};
