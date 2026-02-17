<?php

declare(strict_types=1);

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
        Schema::table('users', function (Blueprint $table): void {
            $table->string('first_name', 80)->nullable()->after('id');
            $table->string('last_name', 80)->nullable()->after('first_name');
            $table->string('phone', 32)->nullable()->after('email');
            $table->boolean('is_active')->default(true)->after('remember_token');
            $table->index(['is_active', 'email_verified_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex('users_is_active_email_verified_at_index');
            $table->dropColumn(['first_name', 'last_name', 'phone', 'is_active']);
        });
    }
};
