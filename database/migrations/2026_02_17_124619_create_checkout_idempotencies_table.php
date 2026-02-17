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
        Schema::create('checkout_idempotencies', function (Blueprint $table): void {
            $table->id();
            $table->string('scope_key', 180);
            $table->string('idempotency_key', 120);
            $table->uuid('cart_id')->nullable();
            $table->foreign('cart_id')->references('id')->on('carts')->nullOnDelete();
            $table->uuid('order_id')->nullable();
            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
            $table->string('request_hash', 64);
            $table->timestamp('expires_at')->index();
            $table->timestamps();

            $table->unique(['scope_key', 'idempotency_key']);
            $table->index(['scope_key', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checkout_idempotencies');
    }
};
