<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Use MorphMap in relationships between models
    |--------------------------------------------------------------------------
    |
    | If true, the morphMap feature is going to be used. The array values that
    | are going to be used are the ones inside the 'user_models' array.
    |
    */
    'models' => [

    ],

    /*
    |--------------------------------------------------------------------------
    | application languages
    |--------------------------------------------------------------------------
    |
    | all the models which extend the MlangModel will have records with those languages.
    |
    */
    'languages' => [],

    /*
    |--------------------------------------------------------------------------
    | default application language
    |--------------------------------------------------------------------------
    |
    | returning the default language for the user's browser language and set it to the application.
    |
    */
    'default_language' => '',

    /*
    |--------------------------------------------------------------------------
    | fallback application language
    |--------------------------------------------------------------------------
    |
    | returning the fallback language if there's no default language.
    |
    */
    'fallback_language' => '',

];
