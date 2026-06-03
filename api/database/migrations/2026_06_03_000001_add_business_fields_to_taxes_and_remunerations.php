<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_taxes', function (Blueprint $table) {
            $table->foreignId('business_id')->nullable()->after('id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('business_branch_id')->nullable()->after('business_id')->constrained('business_branches')->cascadeOnDelete();
            $table->string('name')->nullable()->after('business_branch_id');
            $table->decimal('rate', 12, 2)->default(0)->after('name');
            $table->string('type')->nullable()->after('rate');
            $table->text('description')->nullable()->after('type');
            $table->string('status')->default('active')->after('description');
        });

        Schema::table('employee_remunerations', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_remunerations', 'business_id')) {
                $table->foreignId('business_id')->nullable()->after('worker_id')->constrained('businesses')->cascadeOnDelete();
            }
            if (!Schema::hasColumn('employee_remunerations', 'business_branch_id')) {
                $table->foreignId('business_branch_id')->nullable()->after('business_id')->constrained('business_branches')->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('business_taxes', function (Blueprint $table) {
            $table->dropForeign(['business_branch_id']);
            $table->dropForeign(['business_id']);
            $table->dropColumn(['business_branch_id', 'business_id', 'name', 'rate', 'type', 'description', 'status']);
        });

        Schema::table('employee_remunerations', function (Blueprint $table) {
            if (Schema::hasColumn('employee_remunerations', 'business_branch_id')) {
                $table->dropForeign(['business_branch_id']);
                $table->dropColumn('business_branch_id');
            }
            if (Schema::hasColumn('employee_remunerations', 'business_id')) {
                $table->dropForeign(['business_id']);
                $table->dropColumn('business_id');
            }
        });
    }
};
