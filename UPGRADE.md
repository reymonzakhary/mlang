# Upgrade Guide

## 2.x → 3.x

`composer require upon/mlang:^3.0`

**Requirements:** PHP 8.3+ and Laravel 11, 12 or 13. On PHP 8.1/8.2 or Laravel 10 stay on `^2.1`.

**No database changes.** Your `row_id` / `iso` columns and data are used as-is.

### Check these four things

1. **`trFind()` is now a static method.** `Product::trFind(1)` works unchanged. If you called it on a
   query builder (`Product::where(...)->trFind(1)`) use the new builder scope:
   `Product::where(...)->whereRow(1)->first()`.
2. **Overrides of `scopeTrWhere` / `scopeTrFind`** must adopt the new signatures
   (`scopeTrWhere(Builder $builder, ...)`; `trFind` is static).
3. **Locale middleware.** `DetectUserLanguageMiddleware` now also honours a `{locale}` route parameter
   and `?lang=`. If your app sets the locale earlier (e.g. from the user profile) it is kept when nothing
   else is detected. Reading the first URL segment (`/fr/...`) is opt-in: add `'segment'` to
   `detect_locale_from`.
4. **Unguarded models.** Models with `$guarded = []` previously lost all attributes on `create()` (only
   `iso`/`row_id` were fillable). They now mass-assign everything, as Laravel intends.

### Recommended after upgrading

```bash
php artisan mlang:migrate   # adds the (row_id, iso) unique index when your data allows it
php artisan mlang:doctor    # reports duplicates, orphans, unknown locales, coverage
```

If `mlang:migrate` warns that duplicate `(row_id, iso)` pairs exist, resolve them (delete or re-key the
extra rows) and run it again. Set `'unique_row_locale' => false` to skip the index entirely.

## 3.0 → 3.1

`composer update upon/mlang` — nothing changes unless you opt in.

### Optional: ULID / UUID row ids

By default `row_id` stays an integer copied from the first row's primary key. If you share ids across
services, want to generate them client-side, or need them globally unique, switch:

```php
// config/mlang.php
'row_id_type' => 'ulid',   // or 'uuid'
```

```bash
php artisan mlang:doctor                     # tells you which tables still have integer row ids
php artisan mlang:migrate --convert-row-id   # converts them
```

The conversion, per table, inside chunked transactions:

- renames the integer column to `legacy_row_id` (indexed, kept forever unless you drop it)
- adds a new `row_id` (ulid/uuid) column and assigns one id per group of translations
- adds the `(row_id, iso)` unique index

**What keeps working:** `trFind(123)`, `whereRow(123)`, and route-model binding on `/products/123`
resolve integers through `legacy_row_id`, so old URLs and references stored elsewhere continue to work.
`mlang:doctor` reports how many rows still carry a legacy id.

**What you must do yourself:** any code or other service that *creates* references should start using
the new `row_id` value (it is what events, `translations`, and API output expose). When nothing depends
on integers any more you may drop `legacy_row_id`.

**Large tables:** the rename + backfill locks the table on MySQL. Run it in a maintenance window, or
convert one table at a time with `--table=products`.

**Rolling back:** restore from backup — the conversion is not reversible in place.
