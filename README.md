#High-Performance Multi-Language Package for Laravel

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

This package is a high-performance solution designed to provide efficient multi-language support for Laravel applications. It offers robust features and optimizations to ensure fast and reliable language management, making your application accessible to a global audience without sacrificing performance.

##Features
* [Accelerated translation retrieval](#Accelerated translation retrieval): Translation strings are efficiently retrieved from the same database table, minimizing database join queries and improving response times.
* [Seamless integration with Laravel](#Seamless integration with Laravel): The package integrates seamlessly with Laravel, leveraging its existing localization infrastructure while providing performance enhancements.

* [Translation helpers](#Translation helpers): You can use helper functions and methods to translate your application's content into different languages. These helpers make it easy to retrieve and display translated strings.
* [Language detection](#Language detection): The package includes automatic language detection based on the user's browser settings. It also supports manual language selection for users.
* [Language fallbacks](#Language fallbacks): If a translation is missing for a specific language, the package supports fallbacks to default or alternative languages. This ensures that users always see content in a supported language.

## Requirements

* PHP >= 7.4
* Laravel >= 8.0 

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

You can specify the supported locales, default locale, language file path, and other options.\
Add the locale middleware to your app/Http/Kernel.php file in the $middlewareGroups property:
```php
 protected $middlewareGroups = [
'web' => [
// ...
\YourVendorName\MultiLanguagePackage\Middleware\SetLocale::class,
],

    // ...
];
```
Use the provided helper functions and methods to translate your application's content. For example:
```php
// Retrieve a translated string
echo __('messages.welcome');

// Translate a string with placeholders
echo __('messages.greeting', ['name' => 'John']);
```
## Usage
###Managing Language Files
* To add a new language file, use the following command:
```php
/** Replace {locale} with the desired language code (e.g., en for English).  */
php artisan multi-language-package:add-language {locale}
```
* To edit an existing language file, use the following command:
```php
php artisan multi-language-package:edit-language {locale}
```
* To delete a language file, use the following command:
```php
php artisan multi-language-package:delete-language {locale}
```

###Localization URLs
By default, the package uses URL localization by appending language codes to your application's URLs. For example:

> /**en**/about for the English version of the "about" page.
> 
> /**fr**/about for the French version of the "about" page

To generate localized URLs, you can use the localized_url() helper function:
```php
$url = localized_url('about');
```
This will generate the appropriate localized URL based on the current language.

Language Detection and Selection
The package includes automatic language detection based on the user's browser's language. It uses the Accept-Language header or browser settings to determine the preferred language.

To manually set the language for a user, you can use the setLocale() method:
```php
/** Replace {locale} with the desired language code. */
app()->setLocale('{locale}');
```
###Contributing

Contributions are welcome! If you encounter any issues, have suggestions, or want to contribute to the package, please create an issue or submit a pull request on the package's GitHub repository.

###License

This package is open-source software licensed under the MIT license. Feel free to use, modify, and distribute it as per the terms of the license.

Credits

This package was developed by Your Name. Special thanks to the Laravel community for their support and inspiration.

Contact

If you have any questions or need further assistance, you can reach out to the package maintainer at your-email@example.com.
