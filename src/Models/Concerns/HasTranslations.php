<?php

namespace Upon\Mlang\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Upon\Mlang\Events\TranslationMissing;
use Upon\Mlang\Helpers\LanguageHelper;

/**
 * Eloquent-native access to the sibling rows that make up a translated record.
 *
 * Every translation of a record shares the same `row_id` and differs by `iso`,
 * so the relations below are plain HasMany relations keyed on `row_id`.
 */
trait HasTranslations
{
    /**
     * Attributes whose value differs per language. Everything else is treated as
     * "shared" and is kept in sync across sibling rows (see MlangObserver@updated).
     *
     * Leave empty to disable shared-attribute syncing for this model.
     *
     * @var string[]
     */
    // protected array $translatable = ['name', 'description'];

    /**
     * All rows (every locale, including this one) that belong to the same record.
     *
     * @return HasMany
     */
    public function translations(): HasMany
    {
        return $this->hasMany(static::class, 'row_id', 'row_id');
    }

    /**
     * All sibling rows except the current one.
     *
     * @return HasMany
     */
    public function otherTranslations(): HasMany
    {
        return $this->translations()->where('iso', '!=', $this->iso);
    }

    /**
     * Get the row for a specific locale (defaults to the current app locale).
     *
     * Uses the loaded `translations` relation when available to avoid extra queries.
     * Fires TranslationMissing when nothing is found. Returns the fallback-locale
     * row instead of null when $fallback is true.
     *
     * @param string|null $locale
     * @param bool        $fallback
     * @return static|null
     */
    public function translation(?string $locale = null, bool $fallback = false): ?static
    {
        $locale = $locale ?? app()->getLocale();

        $found = $this->relationLoaded('translations')
            ? $this->translations->firstWhere('iso', $locale)
            : $this->translations()->where('iso', $locale)->first();

        if ($found) {
            return $found;
        }

        if ($this->row_id !== null) {
            TranslationMissing::dispatch(static::class, $this->row_id, $locale);
        }

        $fallbackLocale = LanguageHelper::getFallbackLanguage();

        if ($fallback && $locale !== $fallbackLocale) {
            return $this->translation($fallbackLocale);
        }

        return null;
    }

    /**
     * Whether a row exists for the given locale.
     *
     * @param string $locale
     * @return bool
     */
    public function hasTranslation(string $locale): bool
    {
        return in_array($locale, $this->availableLocales(), true);
    }

    /**
     * Locales that have a row for this record.
     *
     * @return string[]
     */
    public function availableLocales(): array
    {
        $isos = $this->relationLoaded('translations')
            ? $this->translations->pluck('iso')
            : $this->translations()->pluck('iso');

        return $isos->unique()->values()->all();
    }

    /**
     * Configured locales that do not have a row for this record yet.
     *
     * @return string[]
     */
    public function missingLocales(): array
    {
        return array_values(array_diff(
            LanguageHelper::getConfiguredLanguages(),
            $this->availableLocales()
        ));
    }

    /**
     * Attributes that are language-specific for this model.
     *
     * @return string[]
     */
    public function getTranslatableAttributes(): array
    {
        return property_exists($this, 'translatable') ? (array) $this->translatable : [];
    }

    /**
     * Attributes that must be identical across all translation rows:
     * everything that is not translatable and not an MLang/primary-key/timestamp column.
     *
     * @return string[]
     */
    public function getSharedAttributes(): array
    {
        $excluded = array_merge(
            $this->getTranslatableAttributes(),
            [$this->getKeyName(), 'row_id', 'iso', $this->getCreatedAtColumn(), $this->getUpdatedAtColumn()],
        );

        return array_values(array_diff(array_keys($this->getAttributes()), array_filter($excluded)));
    }

    /**
     * Eager-load all translations: Product::withTranslations()->get().
     *
     * @param Builder $builder
     * @return Builder
     */
    public function scopeWithTranslations(Builder $builder): Builder
    {
        return $builder->with('translations');
    }

    /**
     * Constrain the query to rows in the given locale (defaults to the app locale).
     *
     * @param Builder     $builder
     * @param string|null $locale
     * @return Builder
     */
    public function scopeInLocale(Builder $builder, ?string $locale = null): Builder
    {
        return $builder->where($builder->qualifyColumn('iso'), $locale ?? app()->getLocale());
    }

    /**
     * Constrain the query to rows in the given locale, falling back to the
     * configured fallback locale for records that have no row in that locale.
     *
     * Product::withFallback()->get()       // current locale, fallback where missing
     * Product::withFallback('de')->get()
     *
     * @param Builder     $builder
     * @param string|null $locale
     * @return Builder
     */
    public function scopeWithFallback(Builder $builder, ?string $locale = null): Builder
    {
        $locale = $locale ?? app()->getLocale();
        $fallback = LanguageHelper::getFallbackLanguage();

        if ($locale === $fallback) {
            return $this->scopeInLocale($builder, $locale);
        }

        $table = $this->getTable();
        $iso = $builder->qualifyColumn('iso');

        return $builder->where(function (Builder $query) use ($locale, $fallback, $table, $iso) {
            $query->where($iso, $locale)
                ->orWhere(function (Builder $query) use ($locale, $fallback, $table, $iso) {
                    $query->where($iso, $fallback)
                        ->whereNotExists(function ($sub) use ($locale, $table) {
                            $sub->from($table, 'mlang_fb')
                                ->whereColumn('mlang_fb.row_id', "{$table}.row_id")
                                ->where('mlang_fb.iso', $locale);
                        });
                });
        });
    }

    /**
     * Group a collection of rows by row_id: [row_id => Collection<locale rows>].
     *
     * @param Collection $rows
     * @return Collection
     */
    public static function groupTranslations(Collection $rows): Collection
    {
        return $rows->groupBy('row_id');
    }
}
