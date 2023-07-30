# High-Performance Multi-Language Package for Laravel

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

This package is a high-performance solution designed to provide efficient multi-language support for Laravel applications. It offers robust features and optimizations to ensure fast and reliable language management, making your application accessible to a global audience without sacrificing performance.

## Features

* [Accelerated translation retrieval](#Acceleratedtranslationretrieval): Translation strings are efficiently retrieved from the same database table, minimizing database join queries and improving response times.
* [Seamless integration with Laravel](#Seamless integration with Laravel): The package integrates seamlessly with Laravel, leveraging its existing localization infrastructure while providing performance enhancements.


* [Translation helpers](#Translation helpers): You can use helper functions and methods to translate your application's content into different languages. These helpers make it easy to retrieve and display translated strings.
* [Language detection](#Language detection): The package includes automatic language detection based on the user's browser settings. It also supports manual language selection for users.
* [Language fallbacks](#Language fallbacks): If a translation is missing for a specific language, the package supports fallbacks to default or alternative languages. This ensures that users always see content in a supported language.

#### Notice
This package will add two extra columns to the model you will use, row_id and iso.
The package will use the row_id as id, to grep the right row from the db. 

* [row_id](#row_id): This column will hold the main primary key for you on all languages. 
* [iso](#iso): This column holding the language key, e.g. en,nl,fr,etc... 
## Requirements

* PHP >= 7.4
* Laravel >= 8.0 

*** This package requires the following package. ***
```php
composer require doctrine/dbal
```

## Installation
1 - Install the package using Composer:
```php
composer require upon/mlang
```
2 - Publish the package configuration and language files:
```php
php artisan vendor:publish --tag="mlang"
```
3 - Configure the package by modifying the ``config/mlang.php`` file according to your requirements.

You can specify the supported locales, default locale, models, and other options.

4 - Configure in your mlang config file the needed model for translations. 

```php
// ...
'models' => [
    \App\Models\Category::class,
    \App\Models\Product::class,
    // ...
]
// ...
```

5- Replace the default extended Model with the `MlangModel`.

```php
<?php
// ...
use Upon\Mlang\Models\MlangModel;

class Category extends MlangModel
{
    // ...
```
6 - Run the migration command to migrate the new columns, to the added models.

```shell
    php artisan mlang:migrate
```



###Managing Translations to existing records
* To generate translations for existing rows, use the following command:
```php
php artisan mlang:generate
```
* To generate in a specific table, use the following command:
```php
/** Replace {model} with the desired model you need 
 * (e.g., category or in subdirectory dir\\Category).
 * This using the default model path [App\\Models]
 * you can update the model path in the config file.  
 */
php artisan mlang:generate {model}
```

* To generate a specific language code, use the following command:
```php
/** 
 * all to run all models or specify one model name (e.g., Sub\\Category).
 * Replace {locale} with the desired language code (e.g., en for English).
 */
php artisan mlang:generate {model|all} {locale}
```


* To delete a language from table, use the following command:
```php
/**
 * Replace {locale} with the desired language code (e.g., en for English).
 * Replace {table} with the desired table name (e.g., categories).  
 */
php artisan mlang:remove {table} {locale}
```

- The package includes automatic language detection based on the user's browser's language. It uses the Accept-Language header or browser settings to determine the preferred language.
    - <span style="color: #1589F0;">(Optional)</span> Add the locale middleware to your app/Http/Kernel.php file in the $middlewareGroups property:
      To detect the user browser language.

```php
 protected $middlewareGroups = [
'web' => [
// ...
\Upon\Mlang\Middleware\DetectUserLanguageMiddleware::class,
],

    // ...
];
```

To manually set the language for a user, you can use the setLocale() method:
```php
/** Replace {locale} with the desired language code. */
app()->setLocale('{locale}');
```

## Usage

To find a record from db by id, you can use the following. \
This will get the translated record based on the current language has been used by your application.
```php
    Model::trFind(1);
```
The trWhere method will work like the normal where but will map the `id` to `row_id` column to be able selecting the current language.
This will return a build,  so you can chain on it with other query.
```php
    /** This will get a collection based on the current language*/
    Model::trWhere(['name' => 'test'])->get();
```

###Contributing

Contributions are welcome! If you encounter any issues, have suggestions, or want to contribute to the package, please create an issue or submit a pull request on the package's GitHub repository.

###License

This package is open-source software licensed under the MIT license. Feel free to use, modify, and distribute it as per the terms of the license.

Credits

This package was developed by Reymon Zakhary. Special thanks to the Laravel community for their support and inspiration.

Contact

If you have any questions or need further assistance, you can reach out to the package maintainer at reymon@charisma-design.eu.
