<?php

namespace Upon\Mlang\Columns;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Upon\Mlang\Helpers\RowIdHelper;

/**
 * Adds, converts and removes the MLang columns on a translatable table.
 */
class AddRowIdColumn extends Migration
{
    public const UNIQUE_INDEX = 'mlang_row_id_iso_unique';

    /**
     * Add row_id / iso (and the unique index when the data allows it).
     *
     * @param string $table
     * @param string $model
     * @return void
     */
    public static function up(
        $table,
        $model
    ): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        if (!Schema::hasColumn($table, 'row_id')) {
            Schema::table($table, function (Blueprint $blueprint) use ($model) {
                self::rowIdColumn($blueprint, $model)->nullable()->index();
            });
        }

        if (!Schema::hasColumn($table, 'iso')) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->string('iso')->nullable();
            });
        }

        self::ensureUniqueIndex($table);
        RowIdHelper::flushCache();
    }

    /**
     * Convert an integer row_id column to the configured ulid/uuid type.
     *
     * The integer values move to `legacy_row_id` so existing URLs and
     * references keep resolving; every group of translations gets one new id.
     *
     * @param string $table
     * @return int Number of translation groups converted
     */
    public static function convert(string $table): int
    {
        if (!RowIdHelper::isGenerated()) {
            throw new \RuntimeException("Set mlang.row_id_type to 'ulid' or 'uuid' before converting.");
        }

        if (!Schema::hasColumn($table, 'row_id')) {
            throw new \RuntimeException("Table '{$table}' has no row_id column; run mlang:migrate first.");
        }

        if (!self::isIntegerColumn($table, 'row_id')) {
            return 0; // already converted
        }

        $legacyIndex = "{$table}_" . RowIdHelper::LEGACY_COLUMN . '_index';

        Schema::table($table, function (Blueprint $blueprint) use ($table) {
            if (self::hasIndex($table, self::UNIQUE_INDEX)) {
                $blueprint->dropUnique(self::UNIQUE_INDEX);
            }
            if (self::hasIndex($table, "{$table}_row_id_index")) {
                $blueprint->dropIndex("{$table}_row_id_index");
            }
        });

        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->renameColumn('row_id', RowIdHelper::LEGACY_COLUMN);
        });

        Schema::table($table, function (Blueprint $blueprint) use ($legacyIndex) {
            $blueprint->index(RowIdHelper::LEGACY_COLUMN, $legacyIndex);
        });

        Schema::table($table, function (Blueprint $blueprint) {
            RowIdHelper::type() === RowIdHelper::TYPE_UUID
                ? $blueprint->uuid('row_id')->nullable()->index()
                : $blueprint->ulid('row_id')->nullable()->index();
        });

        $converted = 0;

        DB::table($table)
            ->select(RowIdHelper::LEGACY_COLUMN)
            ->whereNotNull(RowIdHelper::LEGACY_COLUMN)
            ->distinct()
            ->orderBy(RowIdHelper::LEGACY_COLUMN)
            ->chunk(500, function ($groups) use ($table, &$converted) {
                DB::transaction(function () use ($groups, $table, &$converted) {
                    foreach ($groups as $group) {
                        $legacy = $group->{RowIdHelper::LEGACY_COLUMN};
                        $new = RowIdHelper::type() === RowIdHelper::TYPE_UUID ? (string) Str::uuid() : (string) Str::ulid();

                        DB::table($table)->where(RowIdHelper::LEGACY_COLUMN, $legacy)->update(['row_id' => $new]);
                        $converted++;
                    }
                });
            });

        self::ensureUniqueIndex($table);
        RowIdHelper::flushCache();

        return $converted;
    }

    /**
     * Remove the MLang columns.
     *
     * @param string $table
     * @return void
     */
    public static function down(
        $table
    ): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        $columns = array_values(array_filter(
            ['row_id', 'iso', RowIdHelper::LEGACY_COLUMN],
            fn ($column) => Schema::hasColumn($table, $column)
        ));

        if (empty($columns)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($table, $columns) {
            if (self::hasIndex($table, self::UNIQUE_INDEX)) {
                $blueprint->dropUnique(self::UNIQUE_INDEX);
            }
            $blueprint->dropColumn($columns);
        });

        RowIdHelper::flushCache();
    }

    /**
     * Add the (row_id, iso) unique index unless disabled or the data has duplicates.
     *
     * @param string $table
     * @return bool True when the index exists afterwards
     */
    public static function ensureUniqueIndex(string $table): bool
    {
        if (!config('mlang.unique_row_locale', true)) {
            return false;
        }

        if (self::hasIndex($table, self::UNIQUE_INDEX)) {
            return true;
        }

        if (self::duplicateCount($table) > 0) {
            return false;
        }

        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->unique(['row_id', 'iso'], self::UNIQUE_INDEX);
        });

        return true;
    }

    /**
     * Number of (row_id, iso) pairs that occur more than once.
     *
     * @param string $table
     * @return int
     */
    public static function duplicateCount(string $table): int
    {
        return DB::table($table)
            ->select('row_id', 'iso')
            ->whereNotNull('row_id')
            ->groupBy('row_id', 'iso')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();
    }

    /**
     * Whether a column is integer-typed.
     *
     * @param string $table
     * @param string $column
     * @return bool
     */
    public static function isIntegerColumn(string $table, string $column): bool
    {
        $type = strtolower((string) Schema::getColumnType($table, $column));

        return str_contains($type, 'int');
    }

    /**
     * @param string $table
     * @param string $index
     * @return bool
     */
    protected static function hasIndex(string $table, string $index): bool
    {
        return in_array($index, array_column(Schema::getIndexes($table), 'name'), true);
    }

    /**
     * Column definition for a fresh row_id column, based on the configured type.
     *
     * @param Blueprint $blueprint
     * @param string    $model
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    protected static function rowIdColumn(Blueprint $blueprint, string $model): \Illuminate\Database\Schema\ColumnDefinition
    {
        return match (RowIdHelper::type()) {
            RowIdHelper::TYPE_ULID => $blueprint->ulid('row_id'),
            RowIdHelper::TYPE_UUID => $blueprint->uuid('row_id'),
            default => $blueprint->foreignIdFor($model, 'row_id'),
        };
    }
}
