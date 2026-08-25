<?php

namespace Upon\Mlang\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Upon\Mlang\Contracts\TranslatorInterface;
use Upon\Mlang\Events\TranslationCreated;

/**
 * Creates missing locale rows for a model, running the translatable attributes
 * through the configured TranslatorInterface driver.
 */
class AutoTranslateHelper
{
    /**
     * Create every missing locale row for all records of $model.
     *
     * @param Model               $model
     * @param TranslatorInterface $translator
     * @param string[]|null       $targets   Locales to create (default: all configured)
     * @param string|null         $from      Source locale (default: fallback_language)
     * @return int Number of rows created
     */
    public static function fillMissing(
        Model $model,
        TranslatorInterface $translator,
        ?array $targets = null,
        ?string $from = null
    ): int {
        $from = $from ?? LanguageHelper::getFallbackLanguage();
        $targets = array_values(array_diff($targets ?? LanguageHelper::getConfiguredLanguages(), [$from]));
        SecurityHelper::validateLocales($targets);

        $table = $model->getTable();
        $created = 0;

        $sources = $model->newQuery()->where('iso', $from)->whereNotNull('row_id')->get();

        foreach ($sources as $source) {
            $existing = DB::table($table)->where('row_id', $source->row_id)->pluck('iso')->all();

            foreach (array_diff($targets, $existing) as $to) {
                $row = self::buildRow($source, $translator, $from, $to);
                $id = DB::table($table)->insertGetId($row);

                TranslationCreated::dispatch(
                    $model->newInstance()->setRawAttributes($row + ['id' => $id], true),
                    $to
                );
                $created++;
            }
        }

        return $created;
    }

    /**
     * Build the attribute array for one target-locale row.
     *
     * @param Model               $source
     * @param TranslatorInterface $translator
     * @param string              $from
     * @param string              $to
     * @return array
     */
    protected static function buildRow(Model $source, TranslatorInterface $translator, string $from, string $to): array
    {
        $row = $source->getAttributes();
        unset($row[$source->getKeyName()]);

        $translatable = method_exists($source, 'getTranslatableAttributes')
            ? $source->getTranslatableAttributes()
            : [];

        foreach ($translatable as $attribute) {
            if (isset($row[$attribute]) && is_string($row[$attribute]) && $row[$attribute] !== '') {
                $row[$attribute] = $translator->translate($row[$attribute], $from, $to);
            }
        }

        $row['iso'] = $to;
        $row['row_id'] = $source->row_id;

        if ($source->usesTimestamps()) {
            $now = $source->freshTimestampString();
            $row[$source->getCreatedAtColumn()] = $now;
            $row[$source->getUpdatedAtColumn()] = $now;
        }

        return $row;
    }
}
