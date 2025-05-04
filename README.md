# High-Performance Multi-Language Package for Laravel

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

This package is a high-performance solution designed to provide efficient multi-language support for Laravel applications. It offers robust features and optimizations to ensure fast and reliable language management, making your application accessible to a global audience without sacrificing performance.

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

## Using the Facade

MLang comes with a powerful facade that provides a fluent interface for interacting with the package. After installation, you can use the facade as follows:

```php
use Upon\Mlang\Facades\Mlang;
use App\Models\Category;

// Specify which model to work with
Mlang::forModel(Category::class)->migrate();

// Chain multiple operations together
Mlang::forModel(Category::class)
    ->migrate()
    ->generate();

// Get model information
$modelName = Mlang::forModel(Category::class)->getModelName(); // Returns "Category"
$tableName = Mlang::forModel(Category::class)->getTableName(); // Returns the table name

// You can also use a model instance
$category = new Category();
Mlang::forModel($category)->generate();
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

Both package versions provide the same query methods for working with multilingual content:

### Finding Records

To find a record by ID, translated to the current application language:
```php
// Returns the record in the current language
$category = Category::trFind(1);
```

### Querying with Conditions

To query with conditions while respecting language context:
```php
// Gets a collection in the current language
$categories = Category::trWhere(['name' => 'test'])->get();
```

The `trWhere` method works like the normal `where` but automatically maps `id` to `row_id` and adds a language condition, allowing you to chain other query methods:

```php
$categories = Category::trWhere('status', 'active')
                     ->orderBy('created_at', 'desc')
                     ->paginate(10);
```

### Route Model Binding

The package enhances Laravel's route model binding to automatically fetch the correct language version:

```php
// routes/web.php
Route::get('/categories/{category}', function (Category $category) {
    // $category will be in the current application language
    return view('categories.show', compact('category'));
});
```

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

## Contributing

Contributions are welcome! If you encounter any issues, have suggestions, or want to contribute to the package, please create an issue or submit a pull request on the package's GitHub repository.

## License

This package is open-source software licensed under the MIT license. Feel free to use, modify, and distribute it as per the terms of the license.

## Credits

This package was developed by Reymon Zakhary. Special thanks to the Laravel community for their support and inspiration.

## Contact

If you have any questions or need further assistance, you can reach out to the package maintainer at reymon@charisma-design.eu.
