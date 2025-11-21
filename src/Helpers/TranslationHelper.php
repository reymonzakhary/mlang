<?php

namespace Upon\Mlang\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TranslationHelper
{
    /**
     * Get row_id from a model id
     *
     * @param Model $model
     * @param int|string $id
     * @return int|string|null
     */
    public static function getRowIdFromId(Model $model, int|string $id): int|string|null
    {
        $table = $model->getTable();
        $keyName = $model->getKeyName();

        if (!SecurityHelper::columnExists($table, 'row_id')) {
            return null;
        }

        $record = DB::table($table)
            ->where($keyName, $id)
            ->first(['row_id']);

        return $record?->row_id;
    }

    /**
     * Get row_id from model instance or id
     *
     * @param Model $model
     * @param Model|int|string $modelOrId
     * @return int|string|null
     */
    public static function resolveRowId(Model $model, Model|int|string $modelOrId): int|string|null
    {
        // If it's a model instance
        if ($modelOrId instanceof Model) {
            return $modelOrId->row_id ?? self::getRowIdFromId($model, $modelOrId->getKey());
        }

        // If it's an ID
        return self::getRowIdFromId($model, $modelOrId);
    }

    /**
     * Get existing translations for a record
     *
     * @param Model $model
     * @param int|string $rowId
     * @return array Array of locale codes
     */
    public static function getExistingTranslations(Model $model, int|string $rowId): array
    {
        $table = $model->getTable();

        if (!SecurityHelper::columnExists($table, 'row_id') || !SecurityHelper::columnExists($table, 'iso')) {
            return [];
        }

        return DB::table($table)
            ->where('row_id', $rowId)
            ->pluck('iso')
            ->toArray();
    }

    /**
     * Get existing translations for a record by id
     *
     * @param Model $model
     * @param int|string $id
     * @return array Array of locale codes
     */
    public static function getExistingTranslationsById(Model $model, int|string $id): array
    {
        $rowId = self::getRowIdFromId($model, $id);

        if ($rowId === null) {
            return [];
        }

        return self::getExistingTranslations($model, $rowId);
    }

    /**
     * Create translations for multiple languages
     *
     * @param Model $model
     * @param array $attributes Base attributes for the record
     * @param array $languages Languages to create translations for
     * @param array|null $translatedAttributes Optional: different attributes per language
     * @return array Created records
     */
    public static function createMultiLanguageRecord(
        Model $model,
        array $attributes,
        array $languages,
        ?array $translatedAttributes = null
    ): array {
        // Validate languages
        SecurityHelper::validateLocales($languages);

        // Sanitize base attributes
        $attributes = SecurityHelper::sanitizeAttributes($attributes);

        $table = $model->getTable();
        $records = [];
        $rowId = self::generateRowId($model);

        // Track already-used values in this batch to avoid duplicates
        $usedValues = [];

        foreach ($languages as $language) {
            // Get language-specific attributes if provided
            $recordAttributes = $translatedAttributes[$language] ?? $attributes;

            // Merge with base attributes if using translated attributes
            if ($translatedAttributes !== null && isset($translatedAttributes[$language])) {
                $recordAttributes = array_merge($attributes, $translatedAttributes[$language]);
            }

            // Add MLang columns
            $recordAttributes['row_id'] = $rowId;
            $recordAttributes['iso'] = $language;

            // Add timestamps if model uses them
            if ($model->usesTimestamps()) {
                $now = now();
                $recordAttributes['created_at'] = $now;
                $recordAttributes['updated_at'] = $now;
            }

            // Handle unique constraints
            $recordAttributes = self::handleUniqueConstraints($model, $recordAttributes, $language);

            // Ensure unique values within this batch
            $recordAttributes = self::ensureBatchUniqueness($recordAttributes, $usedValues, $language);

            // Track used values
            foreach ($recordAttributes as $key => $value) {
                if (is_string($value)) {
                    $usedValues[$key][] = $value;
                }
            }

            $records[] = $recordAttributes;
        }

        // Insert one by one to avoid batch unique constraint violations
        // This is safer than batch insert when dealing with unique constraints
        $insertedRecords = [];
        foreach ($records as $record) {
            try {
                $id = DB::table($table)->insertGetId($record);
                $record['id'] = $id;
                $insertedRecords[] = $record;
            } catch (\Exception $e) {
                // If insert fails, try with more aggressive unique handling
                $record = self::makeMoreUnique($model, $record);
                try {
                    $id = DB::table($table)->insertGetId($record);
                    $record['id'] = $id;
                    $insertedRecords[] = $record;
                } catch (\Exception $e2) {
                    // Give up on this record
                    continue;
                }
            }
        }

        return $insertedRecords;
    }

    /**
     * Ensure uniqueness within the current batch
     *
     * @param array $attributes
     * @param array $usedValues
     * @param string $language
     * @return array
     */
    private static function ensureBatchUniqueness(array $attributes, array $usedValues, string $language): array
    {
        foreach ($attributes as $key => $value) {
            if (is_string($value) && isset($usedValues[$key]) && in_array($value, $usedValues[$key])) {
                // Value already used in this batch, make it unique
                $attributes[$key] = $value . '-' . $language;
            }
        }

        return $attributes;
    }

    /**
     * Make record more unique by adding random suffix
     *
     * @param Model $model
     * @param array $record
     * @return array
     */
    private static function makeMoreUnique(Model $model, array $record): array
    {
        $uniqueIndexes = self::getUniqueIndexes($model->getTable());

        foreach ($uniqueIndexes as $index) {
            foreach ($index['columns'] as $column) {
                if (isset($record[$column]) && is_string($record[$column])) {
                    $record[$column] = $record[$column] . '-' . \Illuminate\Support\Str::random(4);
                }
            }
        }

        return $record;
    }

    /**
     * Generate a new row_id for translations
     *
     * @param Model $model
     * @return int
     */
    public static function generateRowId(Model $model): int
    {
        $table = $model->getTable();
        $maxRowId = DB::table($table)->max('row_id');

        return ($maxRowId ?? 0) + 1;
    }

    /**
     * Handle unique constraints by appending suffixes
     *
     * @param Model $model
     * @param array $attributes
     * @param string $language
     * @return array
     */
    public static function handleUniqueConstraints(Model $model, array $attributes, string $language): array
    {
        $table = $model->getTable();
        $uniqueIndexes = self::getUniqueIndexes($table);

        foreach ($uniqueIndexes as $index) {
            $columns = $index['columns'];

            // Skip if index includes iso (already unique per language)
            if (in_array('iso', $columns, true)) {
                continue;
            }

            // For each column in the unique index, ensure uniqueness
            foreach ($columns as $column) {
                if (!isset($attributes[$column]) || !is_string($attributes[$column])) {
                    continue;
                }

                $originalValue = $attributes[$column];
                $newValue = $originalValue;
                $attempt = 0;
                $maxAttempts = 100;

                // Keep trying until we find a unique value
                while ($attempt < $maxAttempts) {
                    // Build query to check if this value exists
                    $query = DB::table($table)->where($column, $newValue);

                    // For composite unique indexes, check all columns
                    foreach ($columns as $otherColumn) {
                        if ($otherColumn !== $column && isset($attributes[$otherColumn])) {
                            $query->where($otherColumn, $attributes[$otherColumn]);
                        }
                    }

                    // If value doesn't exist, we can use it
                    if (!$query->exists()) {
                        $attributes[$column] = $newValue;
                        break;
                    }

                    // Try with language suffix first
                    if ($attempt === 0) {
                        $newValue = $originalValue . '-' . $language;
                    } else {
                        // Then try with language suffix + number
                        $newValue = $originalValue . '-' . $language . '-' . $attempt;
                    }

                    $attempt++;
                }

                // If we exhausted all attempts, use random suffix as last resort
                if ($attempt >= $maxAttempts) {
                    $attributes[$column] = $originalValue . '-' . $language . '-' . \Illuminate\Support\Str::random(5);
                }
            }
        }

        return $attributes;
    }

    /**
     * Get unique indexes for a table
     *
     * @param string $table
     * @return array
     */
    public static function getUniqueIndexes(string $table): array
    {
        $driver = DB::getDriverName();
        $indexes = [];

        try {
            if ($driver === 'mysql') {
                $results = DB::select("SHOW INDEXES FROM `{$table}` WHERE Non_unique = 0");

                $indexGroups = [];
                foreach ($results as $result) {
                    $indexName = $result->Key_name;
                    if ($indexName === 'PRIMARY') {
                        continue; // Skip primary key
                    }
                    if (!isset($indexGroups[$indexName])) {
                        $indexGroups[$indexName] = [];
                    }
                    $indexGroups[$indexName][] = $result->Column_name;
                }

                foreach ($indexGroups as $name => $columns) {
                    $indexes[] = ['name' => $name, 'columns' => $columns];
                }
            } elseif ($driver === 'pgsql') {
                $schema = DB::connection()->getConfig()['schema'] ?? 'public';
                $results = DB::select("
                    SELECT
                        i.relname AS index_name,
                        array_agg(a.attname ORDER BY array_position(ix.indkey, a.attnum::smallint)) AS columns
                    FROM
                        pg_class t,
                        pg_class i,
                        pg_index ix,
                        pg_attribute a,
                        pg_namespace n
                    WHERE
                        t.oid = ix.indrelid
                        AND i.oid = ix.indexrelid
                        AND a.attrelid = t.oid
                        AND a.attnum = ANY(ix.indkey)
                        AND t.relkind = 'r'
                        AND t.relname = ?
                        AND n.oid = t.relnamespace
                        AND n.nspname = ?
                        AND ix.indisunique = true
                        AND ix.indisprimary = false
                    GROUP BY
                        i.relname
                ", [$table, $schema]);

                foreach ($results as $result) {
                    $indexes[] = [
                        'name' => $result->index_name,
                        'columns' => explode(',', trim($result->columns, '{}'))
                    ];
                }
            }
        } catch (\Exception $e) {
            // If we can't get indexes, return empty array
            return [];
        }

        return $indexes;
    }

    /**
     * Copy record to another language
     *
     * @param Model $model Model class to use for querying
     * @param Model|int|string $sourceModelOrId Source model instance or ID
     * @param string $targetLanguage Target language code
     * @param array $overrideAttributes Optional attributes to override
     * @return Model|null
     */
    public static function copyToLanguage(
        Model $model,
        Model|int|string $sourceModelOrId,
        string $targetLanguage,
        array $overrideAttributes = []
    ): ?Model {
        SecurityHelper::validateLocale($targetLanguage);

        // Get source model instance
        if ($sourceModelOrId instanceof Model) {
            $sourceModel = $sourceModelOrId;
        } else {
            // Find the record by ID in current language
            $sourceModel = $model->find($sourceModelOrId);
            if (!$sourceModel) {
                return null;
            }
        }

        $attributes = $sourceModel->getAttributes();

        // Remove primary key
        unset($attributes[$sourceModel->getKeyName()]);

        // Set target language
        $attributes['iso'] = $targetLanguage;

        // Keep same row_id
        if (!isset($attributes['row_id'])) {
            // If no row_id, try to get it
            $rowId = self::resolveRowId($model, $sourceModel);
            if ($rowId === null) {
                return null;
            }
            $attributes['row_id'] = $rowId;
        }

        // Override with provided attributes
        $attributes = array_merge($attributes, $overrideAttributes);

        // Handle unique constraints
        $attributes = self::handleUniqueConstraints($sourceModel, $attributes, $targetLanguage);

        // Create new record
        return $model->newInstance()->create($attributes);
    }

    /**
     * Delete all translations for a row_id
     *
     * @param Model $model
     * @param int|string $rowId
     * @return int Number of deleted records
     */
    public static function deleteAllTranslations(Model $model, int|string $rowId): int
    {
        $table = $model->getTable();

        if (!SecurityHelper::columnExists($table, 'row_id')) {
            return 0;
        }

        return DB::table($table)->where('row_id', $rowId)->delete();
    }

    /**
     * Delete all translations for a record by id
     *
     * @param Model $model
     * @param int|string $id
     * @return int Number of deleted records
     */
    public static function deleteAllTranslationsById(Model $model, int|string $id): int
    {
        $rowId = self::getRowIdFromId($model, $id);

        if ($rowId === null) {
            return 0;
        }

        return self::deleteAllTranslations($model, $rowId);
    }

    /**
     * Update all translations with common attributes
     *
     * @param Model $model
     * @param int|string $rowId
     * @param array $attributes
     * @return int Number of updated records
     */
    public static function updateAllTranslations(Model $model, int|string $rowId, array $attributes): int
    {
        $table = $model->getTable();

        if (!SecurityHelper::columnExists($table, 'row_id')) {
            return 0;
        }

        // Sanitize attributes
        $attributes = SecurityHelper::sanitizeAttributes($attributes);

        // Add updated_at timestamp if model uses timestamps
        if ($model->usesTimestamps()) {
            $attributes['updated_at'] = now();
        }

        return DB::table($table)->where('row_id', $rowId)->update($attributes);
    }

    /**
     * Update all translations for a record by id
     *
     * @param Model $model
     * @param int|string $id
     * @param array $attributes
     * @return int Number of updated records
     */
    public static function updateAllTranslationsById(Model $model, int|string $id, array $attributes): int
    {
        $rowId = self::getRowIdFromId($model, $id);

        if ($rowId === null) {
            return 0;
        }

        return self::updateAllTranslations($model, $rowId, $attributes);
    }

    /**
     * Get translation statistics for a model
     *
     * @param Model $model
     * @return array
     */
    public static function getTranslationStats(Model $model): array
    {
        $table = $model->getTable();

        if (!SecurityHelper::columnExists($table, 'row_id') || !SecurityHelper::columnExists($table, 'iso')) {
            return ['error' => 'MLang columns not found'];
        }

        $configuredLanguages = LanguageHelper::getConfiguredLanguages();
        $stats = [
            'total_records' => DB::table($table)->count(),
            'unique_records' => DB::table($table)->distinct('row_id')->count('row_id'),
            'languages' => []
        ];

        foreach ($configuredLanguages as $language) {
            $stats['languages'][$language] = DB::table($table)->where('iso', $language)->count();
        }

        return $stats;
    }
}
