<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MaintenanceCleanupSchemaSupportTest extends TestCase
{
    use RefreshDatabase;

    public function test_cleanup_predicates_are_supported_by_explicit_indexes(): void
    {
        $this->assertTableHasIndex(
            'carts',
            'carts_cleanup_status_updated_at_index',
            ['status', 'updated_at'],
        );
        $this->assertTableHasIndex(
            'webhook_receipts',
            'webhook_receipts_cleanup_created_at_index',
            ['created_at'],
        );
    }

    /**
     * @param  list<string>  $expectedColumns
     */
    private function assertTableHasIndex(
        string $table,
        string $indexName,
        array $expectedColumns,
    ): void {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = collect(DB::select(sprintf("PRAGMA index_list('%s')", $table)));
            $this->assertTrue(
                $indexes->contains(static fn (object $index): bool => self::readRowValue($index, 'name') === $indexName),
                sprintf('Table [%s] must contain index [%s].', $table, $indexName),
            );

            $columns = collect(DB::select(sprintf("PRAGMA index_info('%s')", $indexName)))
                ->sortBy(static fn (object $column): string => self::readRowValue($column, 'seqno'))
                ->map(static fn (object $column): string => self::readRowValue($column, 'name'))
                ->values()
                ->all();

            $this->assertSame($expectedColumns, $columns);

            return;
        }

        $indexRows = collect(DB::select(sprintf(
            "SHOW INDEX FROM `%s` WHERE Key_name = '%s'",
            $table,
            $indexName,
        )));

        $this->assertNotEmpty($indexRows, sprintf('Table [%s] must contain index [%s].', $table, $indexName));

        $columns = $indexRows
            ->sortBy(static fn (object $row): string => self::readRowValue($row, 'Seq_in_index'))
            ->map(static fn (object $row): string => self::readRowValue($row, 'Column_name'))
            ->values()
            ->all();

        $this->assertSame($expectedColumns, $columns);
    }

    private static function readRowValue(object $row, string $key): string
    {
        $values = (array) $row;

        return isset($values[$key]) ? (string) $values[$key] : '';
    }
}
