<?php

namespace Upon\Mlang\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Everything about how `row_id` (the entity id shared by all translations of a
 * record) is generated and stored.
 *
 * Types (config `mlang.row_id_type`):
 *   id   – legacy: an integer copied from the primary key of the first row
 *   ulid – a ULID generated before insert; globally unique, sortable
 *   uuid – a UUID v4 generated before insert
 */
class RowIdHelper
{
    public const TYPE_ID = 'id';
    public const TYPE_ULID = 'ulid';
    public const TYPE_UUID = 'uuid';

    public const LEGACY_COLUMN = 'legacy_row_id';

    /**
     * Per-request cache of "does table X have a legacy_row_id column".
     *
     * @var array<string, bool>
     */
    protected static array $legacyColumnCache = [];

    /**
     * The configured row_id type.
     *
     * @return string
     */
    public static function type(): string
    {
        $type = (string) config('mlang.row_id_type', self::TYPE_ID);

        return in_array($type, [self::TYPE_ID, self::TYPE_ULID, self::TYPE_UUID], true) ? $type : self::TYPE_ID;
    }

    /**
     * Whether row_id values are generated up-front (ulid/uuid) rather than
     * copied from the primary key after insert.
     *
     * @return bool
     */
    public static function isGenerated(): bool
    {
        return self::type() !== self::TYPE_ID;
    }

    /**
     * Produce a new row_id for a record of $model.
     *
     * @param Model $model
     * @return string|int
     */
    public static function generate(Model $model): string|int
    {
        return match (self::type()) {
            self::TYPE_ULID => (string) Str::ulid(),
            self::TYPE_UUID => (string) Str::uuid(),
            default => self::nextLegacyId($model),
        };
    }

    /**
     * Legacy integer strategy: max(row_id) + 1.
     *
     * @param Model $model
     * @return int
     */
    public static function nextLegacyId(Model $model): int
    {
        return ((int) DB::table($model->getTable())->max('row_id')) + 1;
    }

    /**
     * Whether $value looks like a legacy integer row_id while the configured
     * type is ulid/uuid – i.e. it must be resolved through legacy_row_id.
     *
     * @param mixed $value
     * @return bool
     */
    public static function isLegacyValue(mixed $value): bool
    {
        return self::isGenerated()
            && (is_int($value) || (is_string($value) && ctype_digit($value)));
    }

    /**
     * Whether $table still carries a legacy_row_id column from a conversion.
     *
     * @param string $table
     * @return bool
     */
    public static function tableHasLegacyColumn(string $table): bool
    {
        return self::$legacyColumnCache[$table] ??= Schema::hasColumn($table, self::LEGACY_COLUMN);
    }

    /**
     * Column name to look a row_id value up in: legacy_row_id for integer
     * values after a conversion, row_id otherwise.
     *
     * @param string $table
     * @param mixed  $value
     * @return string
     */
    public static function lookupColumn(string $table, mixed $value): string
    {
        return self::isLegacyValue($value) && self::tableHasLegacyColumn($table)
            ? self::LEGACY_COLUMN
            : 'row_id';
    }

    /**
     * Forget cached schema lookups (call after a migration).
     *
     * @return void
     */
    public static function flushCache(): void
    {
        self::$legacyColumnCache = [];
    }
}
