<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sync schema updates for already-migrated environments.
     */
    public function up(): void
    {
        $this->syncPaymentsTable();
        $this->syncCheckoutIdempotenciesTable();
    }

    /**
     * Reverse migration.
     */
    public function down(): void
    {
        // Compatibility migration intentionally keeps current schema.
    }

    /**
     * Add payment idempotency column/index for old databases.
     */
    private function syncPaymentsTable(): void
    {
        if (! Schema::hasTable('payments') || Schema::hasColumn('payments', 'idempotency_key')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table): void {
            $table->string('idempotency_key', 120)->nullable();
        });

        DB::table('payments')
            ->select('id')
            ->orderBy('id')
            ->chunkById(100, static function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('payments')
                        ->where('id', $row->id)
                        ->whereNull('idempotency_key')
                        ->update([
                            'idempotency_key' => 'legacy-'.$row->id,
                        ]);
                }
            });

        Schema::table('payments', function (Blueprint $table): void {
            $table->unique(['order_id', 'idempotency_key'], 'payments_order_idempotency_unique');
        });
    }

    /**
     * Add checkout idempotency scope fields for old databases.
     */
    private function syncCheckoutIdempotenciesTable(): void
    {
        if (! Schema::hasTable('checkout_idempotencies')) {
            return;
        }

        $scopeColumnAdded = false;

        if (! Schema::hasColumn('checkout_idempotencies', 'scope_key')) {
            Schema::table('checkout_idempotencies', function (Blueprint $table): void {
                $table->string('scope_key', 180)->default('legacy');
            });

            $scopeColumnAdded = true;
        }

        if (! Schema::hasColumn('checkout_idempotencies', 'cart_id')) {
            Schema::table('checkout_idempotencies', function (Blueprint $table): void {
                $table->uuid('cart_id')->nullable()->index();
            });
        }

        if ($scopeColumnAdded) {
            Schema::table('checkout_idempotencies', function (Blueprint $table): void {
                $table->dropUnique('checkout_idempotencies_idempotency_key_unique');
            });

            Schema::table('checkout_idempotencies', function (Blueprint $table): void {
                $table->unique(
                    ['scope_key', 'idempotency_key'],
                    'checkout_idempotencies_scope_idempotency_unique',
                );
                $table->index(['scope_key', 'expires_at'], 'checkout_idempotencies_scope_expires_idx');
            });
        }
    }
};
