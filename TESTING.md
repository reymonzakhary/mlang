# MLang Package Testing Guide

## Prerequisites
1. Ensure you have run `composer dump-autoload` in your main Laravel app
2. Publish the config: `php artisan vendor:publish --tag=mlang`
3. Configure your models in `config/mlang.php`

## Configuration Example

Edit `config/mlang.php`:

```php
'models' => [
    \App\Models\Category::class,
    \App\Models\Product::class,
],

'languages' => [
    'en',
    'fr',
    'de',
],

'fallback_language' => 'en',
```

## Testing Commands

### 1. Test mlang:migrate

Add columns to all configured models:
```bash
php artisan mlang:migrate
```

Add columns to specific table:
```bash
php artisan mlang:migrate --table=categories
```

Rollback all tables:
```bash
php artisan mlang:migrate --rollback
```

Rollback specific table:
```bash
php artisan mlang:migrate --table=categories --rollback
```

### 2. Test mlang:generate

Generate translations for all models:
```bash
php artisan mlang:generate
```

Generate for specific model:
```bash
php artisan mlang:generate Category
```

Generate for specific model and language:
```bash
php artisan mlang:generate Category fr
```

### 3. Test Facade Methods

Create a test in `routes/web.php` or tinker:

```php
use Upon\Mlang\Facades\MLang;
use App\Models\Category;

// Test forModel
MLang::forModel(Category::class)->migrate();

// Test createMultiLanguage
$categories = MLang::forModel(Category::class)->createMultiLanguage([
    'name' => 'Test Category',
    'slug' => 'test-category',
]);

// Test getStats
$stats = MLang::forModel(Category::class)->getStats();
dd($stats);

// Test getAllTranslations
$translations = MLang::forModel(Category::class)->getAllTranslations(1);
dd($translations);

// Test getCoverage
$coverage = MLang::forModel(Category::class)->getCoverage();
dd($coverage); // Returns percentage like 94.67
```

### 4. Test Helper Classes

```php
use Upon\Mlang\Helpers\SecurityHelper;
use Upon\Mlang\Helpers\LanguageHelper;

// Test SecurityHelper
SecurityHelper::validateLocale('en'); // Returns true
SecurityHelper::validateTableName('categories'); // Returns true
SecurityHelper::sanitizeValue($userInput);

// Test LanguageHelper
$languages = LanguageHelper::getConfiguredLanguages();
$fallback = LanguageHelper::getFallbackLanguage();
$current = LanguageHelper::getCurrentLocale();
```

## Expected Results

After running `mlang:migrate`, your tables should have:
- `row_id` column (integer)
- `iso` column (string, 2 chars)

After running `mlang:generate`, your tables should have:
- Records for each configured language
- Same `row_id` for all language versions
- Different `iso` for each language

## Troubleshooting

### Commands not found
```bash
composer dump-autoload
php artisan clear-compiled
php artisan cache:clear
```

### Service provider not loading
Check `bootstrap/providers.php` or `config/app.php` to ensure:
```php
Upon\Mlang\Providers\MlangServiceProvider::class,
```

For Laravel 11+, the service provider should be auto-discovered.

## Notes
- Always backup your database before running migrations
- Test on development environment first
- Check the logs at `storage/logs/laravel.log` for any errors
