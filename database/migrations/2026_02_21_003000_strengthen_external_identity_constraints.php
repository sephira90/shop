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
        Schema::table('payments', function (Blueprint $table): void {
            $table->unique(['gateway', 'transaction_id'], 'payments_gateway_transaction_unique');
        });

        Schema::table('shipments', function (Blueprint $table): void {
            $table->unique(['provider', 'tracking_number'], 'shipments_provider_tracking_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropUnique('payments_gateway_transaction_unique');
        });

        Schema::table('shipments', function (Blueprint $table): void {
            $table->dropUnique('shipments_provider_tracking_unique');
        });
    }
};
