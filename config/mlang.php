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
        \App\Models\Category::class,
        \App\Models\Product::class
    ],

    'languages' => ['ar','nl','en'],

    'default_language' =>''

];
