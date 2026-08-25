# Changelog

## [3.1.0] - Unreleased

### Added
- **`row_id_type` config** (`id` | `ulid` | `uuid`). With `ulid`/`uuid` the entity id is generated before insert, is globally unique and safe to hand to other services. Default stays `id`.
- **`mlang:migrate --convert-row-id`**: converts integer row ids to the configured type, keeping the integers in `legacy_row_id` so existing URLs and stored references keep resolving (`trFind()`, `whereRow()`, route binding).
- **`(row_id, iso)` unique index** added by `mlang:migrate` when no duplicates exist (`unique_row_locale` config to disable). The command warns instead of failing while duplicates are present.
- **`whereRow($id, $iso)` builder scope** — the chainable counterpart of `trFind()`.
- `RowIdHelper` (type, generation, legacy lookup) and `UPGRADE.md`.
- `mlang:doctor` now checks the row_id column type against `row_id_type`, verifies the unique index, reports legacy rows, and `--fix` assigns generated ids to orphans.

### Changed
- `DetectUserLanguageMiddleware` keeps a supported locale the application already set when nothing is detected (previously forced the fallback). Reading the first URL segment is now opt-in (`'segment'` in `detect_locale_from`).
- `createMultiLanguage()` no longer suffixes `row_id`/`iso` when de-duplicating string values.

## [3.0.0] - Unreleased

### Added
- **Laravel 13 support** (`illuminate/*` `^11.0|^12.0|^13.0`).
- **Test suite on Orchestra Testbench** with a GitHub Actions matrix (PHP 8.3/8.4/8.5 × Laravel 11/12/13).
- Feature tests covering `mlang:migrate`, the observer, `createMultiLanguage()`, `trFind()`, `trWhere()`, `getAllTranslations()` and `getStats()`.
- **Translation relations** (`HasTranslations` concern, included by `MlangTrait`): `translations()`, `otherTranslations()`, `translation($locale, fallback:)`, `availableLocales()`, `missingLocales()`, `hasTranslation()`.
- **Query scopes** `inLocale()`, `withFallback()`, `withTranslations()`.
- **Fallback resolution**: `trFind($id, $iso, $fallback)` and the `fallback_on_query` config key make `trWhere()`/`trFind()` serve the fallback-language row when the requested locale is missing.
- **`$translatable` declaration** with `getTranslatableAttributes()` / `getSharedAttributes()`; the observer now propagates changed shared attributes to sibling rows (`sync_shared_attributes` config key).
- **Events**: `TranslationCreated`, `TranslationMissing`, `SharedAttributesSynced`.
- **`mlang:doctor`** command (`--fix`, `--json`) and `MLang::doctor()`: verifies class/trait/table/columns and reports orphan rows, unknown locales, duplicate `(row_id, iso)` pairs and per-locale coverage. Exit code 1 on problems, so it can gate CI.
- **`mlang:translate`** command and `MLang::forModel()->translateMissing()`: create missing locale rows through a pluggable `TranslatorInterface` driver (`translator` config key). Ships `NullTranslator` (copies text) and `CallbackTranslator`.
- **`Route::localized()`** macro: `/{locale}/...` route group constrained to configured languages with the detection middleware attached.
- `DetectUserLanguageMiddleware` now detects from route parameter, URL segment, `?lang=`, session and `Accept-Language` in a configurable order (`detect_locale_from`, `locale_query_key`) and only accepts configured locales.

### Changed
- **Breaking:** minimum PHP is now `^8.3`; Laravel 10 support dropped. Stay on `^2.1` for older stacks.
- Dev dependency `phpunit/phpunit` constrained to `^11.5.50|^12.5.8` (CVE-2026-24765, Dependabot alert #2); `composer.lock` is no longer committed.
- **Breaking:** `scopeTrWhere()` now receives the query `Builder` as its first argument (as every Eloquent scope must) and returns the `Builder`. Call sites (`Product::trWhere('status', 'active')`) are unchanged; only classes overriding the scope are affected.
- Observer registration in `bootMlangTrait()` no longer uses `Model::observe()`, which instantiates the model mid-boot and throws a `LogicException` on Laravel 13. Events are registered directly instead.
- PHPUnit `^11|^12`; tests use `#[Test]` attributes instead of `@test` annotations.

### Fixed
- Models using `$guarded = []` lost every attribute on `create()`: the trait forced `$fillable = ['iso', 'row_id']`, which overrides "unguarded". Fully unguarded models are now left untouched.
- `trFind()` could never return `null` (Eloquent replaces a null scope result with the query builder). It is now a static method with the same call signature.
- `MLang::getTableNames()` silently skipped every model on Laravel 13 (the boot exception above was swallowed), so `mlang:migrate` reported "No tables were found".

## [2.1.1]

### Added
- **Helper Classes**: New organized helper classes for better code structure
  - `SecurityHelper`: Input validation, SQL injection prevention, sanitization, rate limiting
  - `LanguageHelper`: Language operations, configuration management, locale validation
  - `TranslationHelper`: Translation management, multi-language operations
  - `QueryHelper`: Database query utilities with MLang awareness

- **Multi-Language Operations**: Bulk translation management
  - `createMultiLanguage()`: Create records in multiple languages simultaneously
  - `getAllTranslations()`: Get all language versions of a record
  - `updateAllTranslations()`: Update all translations at once
  - `deleteAllTranslations()`: Delete all translations for a record
  - `copyToLanguage()`: Copy record to another language with overrides

- **Translation Statistics**: Analytics and insights
  - `getStats()`: Get translation statistics (total, unique, per language)
  - `getCoverage()`: Get translation coverage percentage
  - `getIncompleteTranslations()`: Find records with missing translations

- **Security Enhancements**
  - Input validation for models, tables, locales, and columns
  - SQL injection prevention using Query Builder exclusively
  - Automatic sanitization of string values
  - Rate limiting for bulk operations
  - Type safety with PHP 8.3+ strict typing

- **Enhanced composer.json**
  - Added keywords for better discoverability
  - Added homepage and support links
  - Added detailed author information
  - Added dev dependencies (PHPUnit)
  - Added autoload-dev configuration

### Changed
- **Class Naming**: Consistent case for class names
  - `Mlang` → `MLang` for consistency with facade naming
  - Updated all references across the codebase

- **Command Registration**: Commands now register in both console and HTTP contexts
  - Allows `Artisan::call()` to work from web requests
  - Enables facade methods like `MLang::forModel()->migrate()` from controllers

- **PHP Version**: Updated minimum requirement
  - Root app: `^8.1` → `^8.3`
  - Package: Requires `^8.3`

- **Nullable Parameters**: Fixed PHP 8.4 deprecation warnings
  - Updated method signatures to use explicit nullable types (`?string`)

### Fixed
- **Facade References**: Fixed all references to old `Mlang` facade name
  - `MlangServiceProvider.php`: Lines 287, 322
  - `MLangMigrateCommand.php`: Lines 7, 48, 49
  - `MLangGenerateCommand.php`: Lines 11, 191

- **Command Registration**: Commands now work from both CLI and HTTP contexts
  - Fixed service provider to register commands always, not just in console
  - Resolves "Command not found" errors when using facade from controllers

- **PHP 8.4 Compatibility**: Fixed deprecated implicit nullable parameters
  - `MLangGenerateCommand::getModels()`: Added explicit `?string` type
  - `MLangGenerateCommand::getLanguages()`: Added explicit `?string` type

### Documentation
- Enhanced README.md with comprehensive documentation
  - All new methods documented with examples
  - Security best practices section added
  - Helper class method tables with descriptions
  - Multi-language operation examples
  - Translation statistics examples

- Added TESTING.md guide
  - Configuration examples
  - Command usage examples
  - Facade method examples
  - Helper class examples
  - Troubleshooting section

## Version Compatibility

| Package Version | Laravel | PHP | Architecture |
|-----------------|---------|-----|--------------|
| ^1.0 | 10.x | ≥8.1 | Model inheritance |
| ^2.0 | 11.x, 12.x | ≥8.3 | Trait and interface |

## Migration Guide from v1.x to v2.x

### Breaking Changes
None - the API is backward compatible.

### Recommended Updates

1. Update PHP version to 8.3+
2. Update Laravel to 11.x or 12.x
3. Update your models to use traits instead of inheritance:

```php
// Old (v1.x):
use Upon\Mlang\Models\MlangModel;
class Category extends MlangModel { }

// New (v2.x):
use Illuminate\Database\Eloquent\Model;
use Upon\Mlang\Contracts\MlangContractInterface;
use Upon\Mlang\Models\Traits\MlangTrait;

class Category extends Model implements MlangContractInterface {
    use MlangTrait;
}
```

4. Clear all caches:
```bash
composer dump-autoload
php artisan clear-compiled
php artisan cache:clear
php artisan config:clear
```

5. Test your implementation with the new helper methods.
