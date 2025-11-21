<?php

namespace Upon\Mlang\Helpers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class QueryHelper
{
    /**
     * Apply language filter to query
     *
     * @param Builder $query
     * @param string|null $locale
     * @return Builder
     */
    public static function applyLanguageFilter(Builder $query, ?string $locale = null): Builder
    {
        $locale = LanguageHelper::validateAndGetLocale($locale);

        $model = $query->getModel();
        $table = $model->getTable();

        if (SecurityHelper::columnExists($table, 'iso')) {
            $query->where('iso', $locale);
        }

        return $query;
    }

    /**
     * Apply row_id filter to query
     *
     * @param Builder $query
     * @param int|string $rowId
     * @return Builder
     */
    public static function applyRowIdFilter(Builder $query, int|string $rowId): Builder
    {
        $model = $query->getModel();
        $table = $model->getTable();

        if (SecurityHelper::columnExists($table, 'row_id')) {
            $query->where('row_id', $rowId);
        }

        return $query;
    }

    /**
     * Get all translations for a row_id
     *
     * @param Model $model
     * @param int|string $rowId
     * @return \Illuminate\Support\Collection
     */
    public static function getAllTranslations(Model $model, int|string $rowId)
    {
        $table = $model->getTable();

        if (!SecurityHelper::columnExists($table, 'row_id')) {
            return collect([]);
        }

        return $model->where('row_id', $rowId)->get();
    }

    /**
     * Get all translations for a record by id
     *
     * @param Model $model
     * @param int|string $id
     * @return \Illuminate\Support\Collection
     */
    public static function getAllTranslationsById(Model $model, int|string $id)
    {
        $rowId = TranslationHelper::getRowIdFromId($model, $id);

        if ($rowId === null) {
            return collect([]);
        }

        return self::getAllTranslations($model, $rowId);
    }

    /**
     * Find record by row_id and locale
     *
     * @param Model $model
     * @param int|string $rowId
     * @param string|null $locale
     * @return Model|null
     */
    public static function findByRowIdAndLocale(Model $model, int|string $rowId, ?string $locale = null): ?Model
    {
        $locale = LanguageHelper::validateAndGetLocale($locale);
        $table = $model->getTable();

        if (!SecurityHelper::columnExists($table, 'row_id') || !SecurityHelper::columnExists($table, 'iso')) {
            return null;
        }

        return $model->where('row_id', $rowId)->where('iso', $locale)->first();
    }

    /**
     * Get records with incomplete translations
     *
     * @param Model $model
     * @return \Illuminate\Support\Collection
     */
    public static function getRecordsWithIncompleteTranslations(Model $model)
    {
        $table = $model->getTable();

        if (!SecurityHelper::columnExists($table, 'row_id') || !SecurityHelper::columnExists($table, 'iso')) {
            return collect([]);
        }

        $configuredLanguages = LanguageHelper::getConfiguredLanguages();
        $expectedCount = count($configuredLanguages);

        $incompleteRowIds = DB::table($table)
            ->select('row_id')
            ->groupBy('row_id')
            ->havingRaw('COUNT(DISTINCT iso) < ?', [$expectedCount])
            ->pluck('row_id');

        return $model->whereIn('row_id', $incompleteRowIds)->get();
    }

    /**
     * Scope: Only records in current language
     *
     * @param Builder $query
     * @return Builder
     */
    public static function scopeCurrentLanguage(Builder $query): Builder
    {
        return self::applyLanguageFilter($query);
    }

    /**
     * Scope: Only records with complete translations
     *
     * @param Builder $query
     * @return Builder
     */
    public static function scopeWithCompleteTranslations(Builder $query): Builder
    {
        $model = $query->getModel();
        $table = $model->getTable();

        if (!SecurityHelper::columnExists($table, 'row_id') || !SecurityHelper::columnExists($table, 'iso')) {
            return $query;
        }

        $configuredLanguages = LanguageHelper::getConfiguredLanguages();
        $expectedCount = count($configuredLanguages);

        $completeRowIds = DB::table($table)
            ->select('row_id')
            ->groupBy('row_id')
            ->havingRaw('COUNT(DISTINCT iso) = ?', [$expectedCount])
            ->pluck('row_id');

        return $query->whereIn('row_id', $completeRowIds);
    }

    /**
     * Build a query with MLang-aware conditions
     *
     * @param Model $model
     * @param array $conditions
     * @param string|null $locale
     * @return Builder
     */
    public static function buildMlangQuery(Model $model, array $conditions = [], ?string $locale = null): Builder
    {
        $query = $model->newQuery();
        $locale = LanguageHelper::validateAndGetLocale($locale);

        // Apply language filter
        self::applyLanguageFilter($query, $locale);

        // Apply conditions
        foreach ($conditions as $key => $value) {
            if (is_array($value)) {
                $query->whereIn($key, $value);
            } else {
                $query->where($key, $value);
            }
        }

        return $query;
    }

    /**
     * Get translation coverage percentage
     *
     * @param Model $model
     * @return float
     */
    public static function getTranslationCoverage(Model $model): float
    {
        $table = $model->getTable();

        if (!SecurityHelper::columnExists($table, 'row_id') || !SecurityHelper::columnExists($table, 'iso')) {
            return 0.0;
        }

        $configuredLanguages = LanguageHelper::getConfiguredLanguages();
        $expectedCount = count($configuredLanguages);

        $uniqueRecords = DB::table($table)->distinct('row_id')->count('row_id');
        $totalRecords = DB::table($table)->count();

        if ($uniqueRecords === 0) {
            return 0.0;
        }

        $expectedTotal = $uniqueRecords * $expectedCount;

        return ($totalRecords / $expectedTotal) * 100;
    }
}
