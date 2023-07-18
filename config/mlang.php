<?php

return [
    /*
    |--------------------------------------------------------------------------
    | List of models
    |--------------------------------------------------------------------------
    |
    | Add a list of modules you like to use it for translations
    | this will be used for the migrations to add new columns
    | to the listed models.
    |
    */
    'models' => [
        \App\Models\Category::class,
        \App\Models\Product::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | application languages
    |--------------------------------------------------------------------------
    |
    | All the models which extend the MlangModel will have records with those languages.
    |
    */
    'languages' => [
        'en',
//        'nl'
    ],

    /*
    |--------------------------------------------------------------------------
    | fallback application language
    |--------------------------------------------------------------------------
    |
    | The package will determines if there are nog language found then will get the fallback language from the database.
    | You are free to change it to any locale you need.
    |
    */
    'fallback_language' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Auto generate language records
    |--------------------------------------------------------------------------
    |
    | If true, it will generate copies of the created record with all languages are added into the config.
    |
    */
    'auto_generate' => true,

];
