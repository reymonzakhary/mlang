# High-Performance Multi-Language Package for Laravel

<p align="center">
<a href="https://packagist.org/packages/upon/mlang"><img src="https://img.shields.io/packagist/dt/upon/mlang" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/upon/mlang"><img src="https://img.shields.io/packagist/v/upon/mlang" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/upon/mlang"><img src="https://img.shields.io/packagist/l/upon/mlang" alt="License"></a>
<a href="https://packagist.org/packages/upon/mlang"><img src="https://img.shields.io/packagist/php-v/upon/mlang" alt="PHP Version"></a>
<a href="https://packagist.org/packages/upon/mlang"><img src="https://img.shields.io/packagist/stars/upon/mlang" alt="GitHub Stars"></a>
</p>

This package is a high-performance solution designed to provide efficient multi-language support for Laravel applications. It offers robust features and optimizations to ensure fast and reliable language management, making your application accessible to a global audience without sacrificing performance.

## Table of Contents

- [Version Compatibility](#version-compatibility)
- [Features](#features)
- [Database Structure](#database-structure)
- [Requirements](#requirements)
- [Installation](#installation)
- [Artisan Commands](#artisan-commands)
- [Using the Facade](#using-the-facade)
  - [Core Facade Methods](#core-facade-methods)
  - [Migration & Generation Methods](#migration--generation-methods)
  - [Multi-Language Operations](#-multi-language-operations)
  - [Translation Statistics & Analysis](#-translation-statistics--analysis)
- [Configuration Options](#configuration-options)
- [Query Usage](#query-usage)
- [Helper Classes](#-helper-classes)
  - [SecurityHelper](#securityhelper-methods)
  - [LanguageHelper](#languagehelper-methods)
  - [TranslationHelper](#translationhelper-methods)
  - [QueryHelper](#queryhelper-methods)
- [Security Best Practices](#security-best-practices)
- [Contributing](#contributing)
- [License](#license)
- [Credits](#credits)
- [Contact & Support](#contact--support)

## Version Compatibility

This package follows Laravel's major versioning for compatibility.

| Package Version | Supported Laravel Versions | PHP Requirements | Architecture                            |
|-----------------|-----------------------------|------------------|----------------------------------------|
| `^1.0`          | Laravel 10.x                | PHP >= 8.1       | Model inheritance approach             |
| `^2.0`          | Laravel 11.x, 12.x          | PHP >= 8.3       | Trait and interface approach           |

> **Note:** If you are using Laravel 11 or 12, please use version `^2.0` of this package.  
> For Laravel 10, continue using version `^1.0`.
>
> The architecture has changed between versions - v1 uses model inheritance while v2 uses traits and interfaces.

## Features

* **Accelerated translation retrieval**: Translation strings are efficiently retrieved from the same database table, minimizing database join queries and improving response times.
* **Seamless integration with Laravel**: The package integrates seamlessly with Laravel, leveraging its existing localization infrastructure while providing performance enhancements.
* **Translation helpers**: Helper functions and methods to translate your application's content into different languages.
* **Language detection**: Automatic language detection based on user's browser settings or manual language selection.
* **Language fallbacks**: Fallback support for missing translations to ensure users always see content in a supported language.
* **Smart database structure**: Custom columns for efficient language identification and relation management.
* **Robust error handling**: Intelligent error prevention when columns don't exist or when database operations aren't ready.
* **Safe factory integration**: Designed to work safely with factories even before migrations run.
* **Smooth console integration**: Terminal-friendly command output directly in your console.
* **Flexible architecture**:
    * Version 1: Model inheritance approach for simplicity
    * Version 2: Trait-based approach for more flexibility and compatibility with existing inheritance hierarchies
* **Facade Support**: Fluent facade interface for accessing MLang functionality directly
* **🆕 Multi-Language Insert**: Create records in multiple languages simultaneously with a single method call
* **🆕 Security Enhancements**: Built-in input validation, SQL injection prevention, and sanitization
* **🆕 Helper Classes**: Organized helper classes for security, language operations, translations, and queries
* **🆕 Translation Statistics**: Get insights into translation coverage and incomplete translations
* **🆕 Bulk Operations**: Update or delete all translations for a record at once
* **🆕 Rate Limiting**: Protection against abuse with built-in rate limiting for bulk operations

## Database Structure

This package adds two essential columns to the models you specify:

* **row_id**: This column serves as the primary relation key across all languages, linking translations together.
* **iso**: This column holds the language code (e.g., en, fr, nl), identifying which language each record represents.

## Requirements

* PHP >= 8.1 for version 1.x
* PHP >= 8.3 for version 2.x
* Laravel >= 10.0

**This package requires the following dependency:**
```bash
composer require doctrine/dbal
```

## Installation

1. Install the package using Composer:
```bash
composer require upon/mlang
```

2. Publish the package configuration and language files:
```bash
php artisan vendor:publish --tag="mlang"
```

3. Configure the package by modifying the `config/mlang.php` file according to your requirements. You can specify:
    - Supported locales
    - Default locale
    - Translatable models
    - Auto-generation settings
    - Auto-migration hooks
    - Observer behavior

4. Configure your models in the mlang config file:

```php
// config/mlang.php
'models' => [
    \App\Models\Category::class,
    \App\Models\Product::class,
    // Add all translatable models
]
```

5. Use MLang in your models:

**For Version 1.x:**
Extend your models with `MlangModel` instead of the default Eloquent Model:

```php
<?php
// ...
use Upon\Mlang\Models\MlangModel;

class Category extends MlangModel
{
    // ...
}
```

**For Version 2.x:**
Use the trait and implement the interface instead of extending the model:

```php
<?php
// ...
use Illuminate\Database\Eloquent\Model;
use Upon\Mlang\Contracts\MlangContractInterface;
use Upon\Mlang\Models\Traits\MlangTrait;

class Category extends Model implements MlangContractInterface
{
    use MlangTrait;
    
    // ...
}
```

6. Run the migration command to add the required columns to your models:

```bash
php artisan mlang:migrate
```

## Artisan Commands

The package provides several Artisan commands for managing translations:

| Command | Arguments | Options | Description |
|---------|-----------|---------|-------------|
| `mlang:migrate` | - | `--table=TABLE_NAME`<br>`--rollback` | Add MLang columns to tables<br>Use `--rollback` to remove columns |
| `mlang:generate` | `{model?}`<br>`{locale?}` | - | Generate translations for models<br>Optionally specify model name and locale |

### Command Examples

```bash
# Migrate all configured models
php artisan mlang:migrate

# Migrate specific table
php artisan mlang:migrate --table=categories

# Rollback all migrations
php artisan mlang:migrate --rollback

# Rollback specific table
php artisan mlang:migrate --table=categories --rollback

# Generate translations for all models and languages
php artisan mlang:generate

# Generate for specific model
php artisan mlang:generate Category

# Generate for specific model and language
php artisan mlang:generate Category fr

# Generate for all models, specific language
php artisan mlang:generate all fr
```

## Using the Facade

MLang comes with a powerful facade that provides a fluent interface for interacting with the package.

### Core Facade Methods

| Method | Parameters | Returns | Description |
|--------|------------|---------|-------------|
| `forModel()` | `object\|string $model` | `Mlang` | Set the model to work with (chainable) |
| `getModelName()` | - | `string` | Get the current model name |
| `getTableName()` | - | `string\|null` | Get the table name for current model |
| `getTableNames()` | - | `array` | Get all table names from configured models |
| `getModels()` | - | `array` | Get all configured model class names |
| `getCurrentModel()` | - | `string\|null` | Get current model class name |
| `getModelInstance()` | - | `object\|null` | Get instance of current model |

### Migration & Generation Methods

| Method | Parameters | Returns | Description |
|--------|------------|---------|-------------|
| `migrate()` | `?string $table = null` | `Mlang` | Add MLang columns to table (chainable) |
| `rollback()` | `?string $table = null` | `Mlang` | Remove MLang columns from table (chainable) |
| `generate()` | `?string $model = null, ?string $locale = null` | `Mlang` | Generate translations for model (chainable) |

### 🆕 Multi-Language Operations

| Method | Parameters | Returns | Description |
|--------|------------|---------|-------------|
| `createMultiLanguage()` | `array $attributes, ?array $languages = null, ?array $translatedAttributes = null` | `array` | Create record in multiple languages at once |
| `getAllTranslations()` | `int\|string $id` | `Collection` | Get all language versions of a record by ID |
| `updateAllTranslations()` | `int\|string $id, array $attributes` | `int` | Update all translations by ID (returns count) |
| `deleteAllTranslations()` | `int\|string $id` | `int` | Delete all translations by ID (returns count) |
| `copyToLanguage()` | `Model\|int\|string $sourceModelOrId, string $targetLanguage, array $overrideAttributes = []` | `Model\|null` | Copy record to another language (accepts ID or model) |

### 🆕 Translation Statistics & Analysis

| Method | Parameters | Returns | Description |
|--------|------------|---------|-------------|
| `getStats()` | - | `array` | Get translation statistics (total, unique, per language) |
| `getCoverage()` | - | `float` | Get translation coverage percentage |
| `getIncompleteTranslations()` | - | `Collection` | Get records with missing translations |

### Basic Usage Examples

```php
use Upon\Mlang\Facades\MLang;
use App\Models\Category;

// Specify which model to work with
MLang::forModel(Category::class)->migrate();

// Chain multiple operations together
MLang::forModel(Category::class)
    ->migrate()
    ->generate();

// Get model information
$modelName = MLang::forModel(Category::class)->getModelName(); // Returns "Category"
$tableName = MLang::forModel(Category::class)->getTableName(); // Returns the table name

// You can also use a model instance
$category = new Category();
MLang::forModel($category)->generate();
```

### 🆕 Multi-Language Insert (NEW!)

Create a record in multiple languages simultaneously:

```php
use Upon\Mlang\Facades\MLang;
use App\Models\Product;

// Create product in all configured languages
$records = MLang::forModel(Product::class)->createMultiLanguage([
    'name' => 'Product Name',
    'description' => 'Product Description',
    'price' => 99.99
]);

// Create product in specific languages only
$records = MLang::forModel(Product::class)->createMultiLanguage(
    attributes: [
        'name' => 'Product Name',
        'price' => 99.99
    ],
    languages: ['en', 'fr', 'de']
);

// Create with language-specific content
$records = MLang::forModel(Product::class)->createMultiLanguage(
    attributes: [
        'price' => 99.99,
        'status' => 'active'
    ],
    languages: ['en', 'fr'],
    translatedAttributes: [
        'en' => [
            'name' => 'English Product Name',
            'description' => 'English Description'
        ],
        'fr' => [
            'name' => 'Nom du Produit Français',
            'description' => 'Description Française'
        ]
    ]
);
```

### 🆕 Translation Statistics (NEW!)

Get insights into your translations:

```php
use Upon\Mlang\Facades\MLang;
use App\Models\Category;

// Get translation statistics
$stats = MLang::forModel(Category::class)->getStats();
// Returns:
// [
//     'total_records' => 150,
//     'unique_records' => 50,
//     'languages' => [
//         'en' => 50,
//         'fr' => 48,
//         'de' => 45
//     ]
// ]

// Get translation coverage percentage
$coverage = MLang::forModel(Category::class)->getCoverage(); // Returns: 94.67

// Get records with incomplete translations
$incomplete = MLang::forModel(Category::class)->getIncompleteTranslations();
```

### 🆕 Bulk Translation Operations (NEW!)

Manage all translations for a record using its regular ID - no need to know about `row_id`!

```php
use Upon\Mlang\Facades\MLang;
use App\Models\Product;

// Get all translations for a specific product (pass the regular ID)
$productId = 1;
$translations = MLang::forModel(Product::class)->getAllTranslations($productId);
// Returns collection of all language versions (English, French, German, etc.)

// Update all translations at once (common fields like price, status)
$updated = MLang::forModel(Product::class)->updateAllTranslations($productId, [
    'price' => 149.99,
    'status' => 'sale'
]);
// Updates the price and status for all language versions

// Delete all translations for a record (all languages)
$deleted = MLang::forModel(Product::class)->deleteAllTranslations($productId);

// Copy a record to another language - multiple ways:
// 1. Using model instance
$product = Product::find(1);
$frenchProduct = MLang::forModel(Product::class)->copyToLanguage($product, 'fr', [
    'name' => 'Nom du Produit Français'
]);

// 2. Using just the ID (much easier!)
$frenchProduct = MLang::forModel(Product::class)->copyToLanguage(1, 'fr', [
    'name' => 'Nom du Produit Français',
    'description' => 'Description en Français'
]);
```

## Configuration Options

The package offers various configuration options to fine-tune its behavior:

```php
// config/mlang.php

// Control automatic generation of translations
'auto_generate' => false,

// Enable automatic migration after Laravel migrations
'auto_migrate' => false,

// Control observer behavior during console operations
'observe_during_console' => false,

// Auto-generate translations after seeding
'auto_generate_after_seed' => false,

// Specify which models to process after migrations/seeding
'auto_generate_models' => 'all',

// Control rollback behavior
'auto_rollback' => true,

// Toggle debug output
'debug_output' => true,
```

## Working with Factories

The package is designed to work safely with factories even before migrations have run. It intelligently detects when MLang columns don't exist and avoids trying to use them in such cases.

To ensure smooth operation with factories:

1. Set `'auto_generate' => false` in your config file when using factories before migrations.
2. The trait will automatically detect missing columns and adjust behavior accordingly.
3. After running migrations, you can enable auto-generation features.

**Version 2.x additional benefits:**
The trait-based approach in version 2 makes it even easier to work with factories before migrations, as it has enhanced column existence detection and more graceful fallbacks.

## Managing Translations

### Using Artisan Commands

Generate translations for all models:
```bash
php artisan mlang:generate
```

Generate translations for a specific model:
```bash
php artisan mlang:generate {model}
# Example: php artisan mlang:generate Category
# Or for nested models: php artisan mlang:generate Shop\\Product
```

Generate for a specific language:
```bash
php artisan mlang:generate {model|all} {locale}
# Example: php artisan mlang:generate all fr
```

Remove a language from a table:
```bash
php artisan mlang:remove {table} {locale}
# Example: php artisan mlang:remove categories fr
```

### Using the Facade

You can also manage translations programmatically using the facade:

```php
use Upon\Mlang\Facades\Mlang;
use App\Models\Category;

// Generate translations for a specific model
Mlang::forModel(Category::class)->generate();

// Generate translations for a specific model and language
Mlang::generate('Category', 'fr');

// Run migrations for a specific model
Mlang::forModel(Category::class)->migrate();

// Roll back migrations for a specific model
Mlang::forModel(Category::class)->rollback();
```

### Language Detection

To automatically detect the user's browser language:

1. Add the locale middleware to your `app/Http/Kernel.php` file:

```php
protected $middlewareGroups = [
    'web' => [
        // ...
        \Upon\Mlang\Middleware\DetectUserLanguageMiddleware::class,
    ],
    // ...
];
```

To manually set the language:
```php
app()->setLocale('fr');
```

## Query Usage

Both package versions provide the same query methods for working with multilingual content.

### Model Query Scopes

| Scope Method | Parameters | Description | Example |
|--------------|------------|-------------|---------|
| `trFind()` | `int\|string $id, ?string $iso = null` | Find record by row_id in current (or specified) language | `Category::trFind(1)` |
| `trWhere()` | `array\|string\|Closure $conditions` | Query with auto language filter and id→row_id mapping | `Category::trWhere('status', 'active')` |

### Finding Records

```php
// Find by row_id in current language
$category = Category::trFind(1);

// Find in specific language
$category = Category::trFind(1, 'fr');
```

### Querying with Conditions

```php
// Query in current language
$categories = Category::trWhere(['name' => 'test'])->get();

// Chain other query methods
$categories = Category::trWhere('status', 'active')
                     ->orderBy('created_at', 'desc')
                     ->paginate(10);

// Complex queries
$categories = Category::trWhere(function($query) {
                         $query->where('price', '>', 100)
                               ->orWhere('featured', true);
                     })->get();
```

### Route Model Binding

The package enhances Laravel's route model binding to automatically fetch the correct language version:

```php
// routes/web.php
Route::get('/categories/{category}', function (Category $category) {
    // $category will be automatically fetched in the current application language
    return view('categories.show', compact('category'));
});
```

**How it works:**
- The `row_id` from the URL is used to find the record
- The current application locale (`app()->getLocale()`) determines the language
- Returns 404 if no translation exists in the current language

## Understanding Interface and Trait Relationship

When implementing MLang v2.x in your models, it's important to understand the relationship between the interface and trait:

1. **The Interface (`MlangContractInterface`)** defines the contract that your models must fulfill to be MLang-compatible.

2. **The Trait (`MlangTrait`)** provides the actual implementation of the methods required by the interface.

You must use both together:
```php
class Category extends Model implements MlangContractInterface
{
    use MlangTrait;
    
    // Your model code...
}
```

This provides several benefits:
- **Contract Enforcement**: Ensures all required methods are available
- **Implementation Reuse**: Reuses code through the trait
- **Flexibility**: Allows customization by overriding trait methods
- **Type Safety**: Provides better IDE support and static analysis

## 🆕 Helper Classes

The package includes organized helper classes for better code organization and reusability.

### SecurityHelper Methods

| Method | Parameters | Returns | Description |
|--------|------------|---------|-------------|
| `validateLocale()` | `string $locale` | `bool` | Validate locale format (throws exception if invalid) |
| `validateTableName()` | `string $table` | `bool` | Validate table name (throws exception if invalid) |
| `validateColumnName()` | `string $column` | `bool` | Validate column name (throws exception if invalid) |
| `validateModelClass()` | `string $model` | `bool` | Validate model class exists (throws exception if invalid) |
| `validateLocales()` | `array $locales` | `bool` | Validate multiple locales at once |
| `tableExists()` | `string $table` | `bool` | Check if table exists in database |
| `columnExists()` | `string $table, string $column` | `bool` | Check if column exists in table |
| `sanitizeValue()` | `mixed $value` | `mixed` | Remove null bytes and control characters |
| `sanitizeAttributes()` | `array $attributes` | `array` | Sanitize all values in array |
| `isValidRowId()` | `mixed $rowId` | `bool` | Check if value is valid row_id (positive integer) |
| `checkRateLimit()` | `string $key, int $maxAttempts = 60, int $decayMinutes = 1` | `bool` | Rate limiting check for bulk operations |

**Example Usage:**
```php
use Upon\Mlang\Helpers\SecurityHelper;

// Validate inputs
SecurityHelper::validateLocale('en');
SecurityHelper::validateTableName('users');
SecurityHelper::validateModelClass(Product::class);

// Check database structure
if (SecurityHelper::tableExists('categories')) {
    if (SecurityHelper::columnExists('categories', 'row_id')) {
        // Proceed with operation
    }
}

// Sanitize user inputs
$clean = SecurityHelper::sanitizeValue($userInput);
$cleanAttributes = SecurityHelper::sanitizeAttributes($request->all());

// Rate limiting
if (SecurityHelper::checkRateLimit('bulk_operation', 100, 1)) {
    // Proceed with bulk operation
}
```

---

### LanguageHelper Methods

| Method | Parameters | Returns | Description |
|--------|------------|---------|-------------|
| `getConfiguredLanguages()` | - | `array` | Get all configured languages from config |
| `getFallbackLanguage()` | - | `string` | Get fallback language from config |
| `getCurrentLocale()` | - | `string` | Get current application locale |
| `isLanguageConfigured()` | `string $locale` | `bool` | Check if language is in configuration |
| `parseAcceptLanguageHeader()` | `?string $header` | `string` | Parse Accept-Language header to get best match |
| `validateAndGetLocale()` | `?string $locale = null` | `string` | Validate locale or return fallback |
| `getMissingLanguages()` | `array $existingLanguages` | `array` | Get languages not in existing array |
| `getLanguageName()` | `string $locale` | `string` | Get human-readable language name |
| `sortLanguagesByPriority()` | `array $languages` | `array` | Sort languages (current first, then config order) |
| `isAutoGenerateEnabled()` | - | `bool` | Check if auto-generation is enabled |
| `shouldObserveDuringConsole()` | - | `bool` | Check if observer runs during console |
| `getConfiguredModels()` | - | `array` | Get all configured model classes |

**Example Usage:**
```php
use Upon\Mlang\Helpers\LanguageHelper;

// Get language configuration
$languages = LanguageHelper::getConfiguredLanguages(); // ['en', 'fr', 'de']
$fallback = LanguageHelper::getFallbackLanguage(); // 'en'
$current = LanguageHelper::getCurrentLocale(); // Current app locale

// Check configuration
if (LanguageHelper::isLanguageConfigured('fr')) {
    // French is configured
}

// Parse browser language
$locale = LanguageHelper::parseAcceptLanguageHeader('fr-FR,fr;q=0.9,en;q=0.8');

// Get missing translations
$missing = LanguageHelper::getMissingLanguages(['en', 'fr']); // ['de']

// Get language name
$name = LanguageHelper::getLanguageName('fr'); // 'French'
```

---

### TranslationHelper Methods

| Method | Parameters | Returns | Description |
|--------|------------|---------|-------------|
| `getExistingTranslations()` | `Model $model, int\|string $rowId` | `array` | Get array of existing locale codes for row_id |
| `createMultiLanguageRecord()` | `Model $model, array $attributes, array $languages, ?array $translatedAttributes = null` | `array` | Create record in multiple languages |
| `generateRowId()` | `Model $model` | `int` | Generate new unique row_id |
| `handleUniqueConstraints()` | `Model $model, array $attributes, string $language` | `array` | Handle unique constraints by appending suffixes |
| `getUniqueIndexes()` | `string $table` | `array` | Get unique indexes for table (DB-agnostic) |
| `copyToLanguage()` | `Model $sourceModel, string $targetLanguage, array $overrideAttributes = []` | `Model\|null` | Copy record to another language |
| `deleteAllTranslations()` | `Model $model, int\|string $rowId` | `int` | Delete all translations for row_id |
| `updateAllTranslations()` | `Model $model, int\|string $rowId, array $attributes` | `int` | Update all translations for row_id |
| `getTranslationStats()` | `Model $model` | `array` | Get statistics (total, unique, per language) |

**Example Usage:**
```php
use Upon\Mlang\Helpers\TranslationHelper;

// Get existing translations
$existing = TranslationHelper::getExistingTranslations($model, $rowId);
// Returns: ['en', 'fr'] if those exist

// Create multi-language records
$records = TranslationHelper::createMultiLanguageRecord(
    $model,
    ['name' => 'Product', 'price' => 99.99],
    ['en', 'fr', 'de']
);

// Copy to another language
$newRecord = TranslationHelper::copyToLanguage($product, 'fr', [
    'name' => 'Nom Français'
]);

// Bulk operations
$count = TranslationHelper::updateAllTranslations($model, $rowId, ['price' => 149.99]);
$deleted = TranslationHelper::deleteAllTranslations($model, $rowId);

// Get statistics
$stats = TranslationHelper::getTranslationStats($model);
// Returns: ['total_records' => 150, 'unique_records' => 50, 'languages' => [...]]
```

---

### QueryHelper Methods

| Method | Parameters | Returns | Description |
|--------|------------|---------|-------------|
| `applyLanguageFilter()` | `Builder $query, ?string $locale = null` | `Builder` | Add language filter to query |
| `applyRowIdFilter()` | `Builder $query, int\|string $rowId` | `Builder` | Add row_id filter to query |
| `getAllTranslations()` | `Model $model, int\|string $rowId` | `Collection` | Get all language versions of record |
| `findByRowIdAndLocale()` | `Model $model, int\|string $rowId, ?string $locale = null` | `Model\|null` | Find specific translation |
| `getRecordsWithIncompleteTranslations()` | `Model $model` | `Collection` | Get records missing some translations |
| `scopeCurrentLanguage()` | `Builder $query` | `Builder` | Scope to current language only |
| `scopeWithCompleteTranslations()` | `Builder $query` | `Builder` | Scope to records with all translations |
| `buildMlangQuery()` | `Model $model, array $conditions = [], ?string $locale = null` | `Builder` | Build query with language awareness |
| `getTranslationCoverage()` | `Model $model` | `float` | Get coverage percentage (0-100) |

**Example Usage:**
```php
use Upon\Mlang\Helpers\QueryHelper;

// Apply filters to query
$query = Product::query();
QueryHelper::applyLanguageFilter($query, 'fr');
QueryHelper::applyRowIdFilter($query, 123);
$products = $query->get();

// Find specific translation
$product = QueryHelper::findByRowIdAndLocale($model, $rowId, 'fr');

// Get all translations for a record
$translations = QueryHelper::getAllTranslations($model, $rowId);

// Get incomplete translations
$incomplete = QueryHelper::getRecordsWithIncompleteTranslations($model);

// Build MLang-aware query
$query = QueryHelper::buildMlangQuery($model, ['status' => 'active'], 'fr');

// Get coverage percentage
$coverage = QueryHelper::getTranslationCoverage($model); // e.g., 94.67
```

## Security Best Practices

This package includes several security features:

1. **Input Validation**: All user inputs (model names, table names, locales) are validated before use
2. **SQL Injection Prevention**: Uses Laravel's Query Builder exclusively, no raw SQL with user input
3. **Sanitization**: Automatic sanitization of string values to remove null bytes and control characters
4. **Rate Limiting**: Built-in rate limiting for bulk operations to prevent abuse
5. **Type Safety**: Strong typing throughout the codebase with PHP 8.3+ features

### Security Guidelines

```php
// ✅ GOOD: Using facade methods (automatically validated)
MLang::forModel(Category::class)->migrate();

// ✅ GOOD: Validated locale
MLang::forModel(Product::class)->generate(locale: 'fr');

// ❌ BAD: Don't use raw SQL with MLang operations
DB::raw("..."); // Not recommended with user input

// ✅ GOOD: Use sanitization for user inputs
$attributes = SecurityHelper::sanitizeAttributes($request->all());
MLang::forModel(Product::class)->createMultiLanguage($attributes);
```

## Contributing

Contributions are welcome! If you encounter any issues, have suggestions, or want to contribute to the package, please create an issue or submit a pull request on the package's GitHub repository.

## License

This package is open-source software licensed under the MIT license. Feel free to use, modify, and distribute it as per the terms of the license.

## Credits

This package was developed by **Reymon Zakhary** at **Charisma Design**.

Special thanks to the Laravel community for their support and inspiration.

### About Charisma Design

Charisma Design is a leading software development company specializing in enterprise solutions and innovative web applications.

- 🌐 Website: [https://chd.com.eg](https://chd.com.eg)
- 🌐 Europe: [https://charisma-design.eu](https://charisma-design.eu)
- 📧 Email: [reymon@charisma-design.eu](mailto:reymon@charisma-design.eu)

## Contact & Support

If you have any questions, need further assistance, or want to discuss enterprise solutions:

- **Email**: [reymon@charisma-design.eu](mailto:reymon@charisma-design.eu)
- **Website**: [https://chd.com.eg](https://chd.com.eg)
- **Europe**: [https://charisma-design.eu](https://charisma-design.eu)
- **GitHub Issues**: [Report bugs or request features](https://github.com/reymonzakhary/pro_mlang/issues)

For enterprise support, custom development, or consulting services, please contact us through our website.
