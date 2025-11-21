<?php

namespace Upon\Mlang\Helpers;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class SecurityHelper
{
    /**
     * Validate a model class name
     *
     * @param string $model
     * @return bool
     * @throws InvalidArgumentException
     */
    public static function validateModelClass(string $model): bool
    {
        // Check if class exists
        if (!class_exists($model)) {
            throw new InvalidArgumentException("Model class '{$model}' does not exist.");
        }

        // Check if it's a valid class name (prevent injection)
        if (!preg_match('/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff\\\\]*$/', $model)) {
            throw new InvalidArgumentException("Invalid model class name format.");
        }

        return true;
    }

    /**
     * Validate a table name
     *
     * @param string $table
     * @return bool
     * @throws InvalidArgumentException
     */
    public static function validateTableName(string $table): bool
    {
        // Only allow alphanumeric characters, underscores, and dots (for database.table notation)
        if (!preg_match('/^[a-zA-Z0-9_\.]+$/', $table)) {
            throw new InvalidArgumentException("Invalid table name format. Only alphanumeric characters, underscores, and dots are allowed.");
        }

        // Check table name length (most databases have a 64 character limit)
        if (strlen($table) > 64) {
            throw new InvalidArgumentException("Table name is too long. Maximum length is 64 characters.");
        }

        return true;
    }

    /**
     * Validate a locale code (ISO 639-1 or custom format)
     *
     * @param string $locale
     * @return bool
     * @throws InvalidArgumentException
     */
    public static function validateLocale(string $locale): bool
    {
        // Allow ISO 639-1 codes (2 letters), ISO 639-2 (3 letters), and locale with region (e.g., en-US)
        if (!preg_match('/^[a-z]{2,3}(-[A-Z]{2})?$/', $locale)) {
            throw new InvalidArgumentException("Invalid locale format. Use ISO format (e.g., 'en', 'fr', 'en-US').");
        }

        return true;
    }

    /**
     * Validate column name
     *
     * @param string $column
     * @return bool
     * @throws InvalidArgumentException
     */
    public static function validateColumnName(string $column): bool
    {
        // Only allow alphanumeric characters and underscores
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $column)) {
            throw new InvalidArgumentException("Invalid column name format.");
        }

        return true;
    }

    /**
     * Sanitize a string value for database operations
     *
     * @param mixed $value
     * @return mixed
     */
    public static function sanitizeValue(mixed $value): mixed
    {
        if (is_string($value)) {
            // Remove null bytes and control characters
            $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);

            // Trim whitespace
            $value = trim($value);
        }

        return $value;
    }

    /**
     * Check if a table exists in the database
     *
     * @param string $table
     * @return bool
     */
    public static function tableExists(string $table): bool
    {
        try {
            self::validateTableName($table);
            return DB::getSchemaBuilder()->hasTable($table);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if a column exists in a table
     *
     * @param string $table
     * @param string $column
     * @return bool
     */
    public static function columnExists(string $table, string $column): bool
    {
        try {
            self::validateTableName($table);
            self::validateColumnName($column);

            if (!self::tableExists($table)) {
                return false;
            }

            return DB::getSchemaBuilder()->hasColumn($table, $column);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Validate multiple locales at once
     *
     * @param array $locales
     * @return bool
     * @throws InvalidArgumentException
     */
    public static function validateLocales(array $locales): bool
    {
        foreach ($locales as $locale) {
            self::validateLocale($locale);
        }

        return true;
    }

    /**
     * Check if value is a valid row_id (positive integer)
     *
     * @param mixed $rowId
     * @return bool
     */
    public static function isValidRowId(mixed $rowId): bool
    {
        return is_numeric($rowId) && $rowId > 0 && floor($rowId) == $rowId;
    }

    /**
     * Sanitize array of attributes for mass assignment
     *
     * @param array $attributes
     * @return array
     */
    public static function sanitizeAttributes(array $attributes): array
    {
        return array_map(function ($value) {
            return self::sanitizeValue($value);
        }, $attributes);
    }

    /**
     * Rate limiting check for bulk operations
     * Returns true if operation should proceed
     *
     * @param string $key
     * @param int $maxAttempts
     * @param int $decayMinutes
     * @return bool
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function checkRateLimit(string $key, int $maxAttempts = 60, int $decayMinutes = 1): bool
    {
        if (!function_exists('cache')) {
            return true; // If cache is not available, allow operation
        }

        $cacheKey = "mlang_rate_limit:{$key}";
        $attempts = cache()->get($cacheKey, 0);

        if ($attempts >= $maxAttempts) {
            return false;
        }

        cache()->put($cacheKey, $attempts + 1, now()->addMinutes($decayMinutes));

        return true;
    }
}
