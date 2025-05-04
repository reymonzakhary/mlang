<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Models path
    |--------------------------------------------------------------------------
    |
    | Add the default models path.
    |
    */
    'default_models_path' => 'App\\Models\\',

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

    /*
    |--------------------------------------------------------------------------
    | Observer in console
    |--------------------------------------------------------------------------
    |
    | The observer registration is now conditionally applied based on the context,
    | with a new config option observe_during_console to control its behavior during console operations.
    |
    */
    'observe_during_console' => false,

    /*
    |--------------------------------------------------------------------------
    | Migration Integration
    |--------------------------------------------------------------------------
    |
    | These settings control how MLang integrates with Laravel migrations.
    | When enabled, migrations will run automatically
    | after Laravel commands.
    |
    */
    'auto_migrate' => env('MLANG_AUTO_MIGRATE', true),

    /*
    |--------------------------------------------------------------------------
    | Auto generate with seed
    |--------------------------------------------------------------------------
    |
    | Enable automatic generation after seeding
    |
    */
    'auto_generate_after_seed' => env('MLANG_AUTO_GENERATE', true),

    /*
    |--------------------------------------------------------------------------
    | Which models to process after migrations/seeding
    |--------------------------------------------------------------------------
    |
    | Which models to process after migrations/seeding
    | Use 'all' for all translatable models or specify a model name
    |
    */
    'auto_generate_models' => env('MLANG_AUTO_GENERATE_MODELS', 'all'),

    /*
    |--------------------------------------------------------------------------
    | Which locale to generate (null for all configured languages)
    |--------------------------------------------------------------------------
    |
    | Select locale to generate (null for all configured languages)
    |
    */
    'auto_generate_locale' => env('MLANG_AUTO_GENERATE_LOCALE', null),

    /*
    |--------------------------------------------------------------------------
    | Auto rollback
    |--------------------------------------------------------------------------
    |
    | Set to false if you like to disable the rollback function
    |
    */
    'auto_rollback' => env('MLANG_ROLLBACK', true),

    /*
    |--------------------------------------------------------------------------
    | Enable logs
    |--------------------------------------------------------------------------
    |
    | Whether to output command results to logs
    |
    */
    'debug_output' => env('MLANG_DEBUG_OUTPUT', true),
];
