<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string("firstname")->nullable();
            $table->string("lastname")->nullable();
            // StoreUserRequest has always declared `unique:users` on this column, but
            // the constraint was never in the schema. The rule then compared the value
            // the client sent against the stored one, which never matched because the
            // service stored a different string, so duplicates slipped through and
            // username sign-in became ambiguous.
            $table->string("username")->nullable()->unique();
            $table->string('email')->unique();
            $table->string('phone')->unique();
            $table->string('address')->nullable();
            $table->string("nin")->nullable()->unique();
            $table->string('password');
            $table->enum("status", ["active", "suspended", "sucked"])->default("active");
            $table->enum("branch_powers", ["allowed", "none"])->default("none");
            $table->foreignId('business_id')->nullable()->constrained("businesses")->cascadeOnDelete();
            $table->foreignId('business_branch_id')->nullable()->constrained("business_branches")->cascadeOnDelete();
            $table->foreignId("role_id")->nullable()->constrained("roles")->cascadeOnDelete();

            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};