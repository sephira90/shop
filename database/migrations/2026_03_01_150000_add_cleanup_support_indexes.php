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
        Schema::table('carts', function (Blueprint $table): void {
            $table->index(
                ['status', 'updated_at'],
                'carts_cleanup_status_updated_at_index',
            );
        });

        Schema::table('webhook_receipts', function (Blueprint $table): void {
            $table->index('created_at', 'webhook_receipts_cleanup_created_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('webhook_receipts', function (Blueprint $table): void {
            $table->dropIndex('webhook_receipts_cleanup_created_at_index');
        });

        Schema::table('carts', function (Blueprint $table): void {
            $table->dropIndex('carts_cleanup_status_updated_at_index');
        });
    }
};
