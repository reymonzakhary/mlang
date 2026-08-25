<?php

namespace Upon\Mlang\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired whenever a translation row is created for a translatable model,
 * either by the observer (source record) or by MlangCreateJob / createMultiLanguage.
 */
class TranslationCreated
{
    use Dispatchable;

    /**
     * @param Model  $translation The row that was created.
     * @param string $locale      The locale of the created row.
     */
    public function __construct(
        public Model $translation,
        public string $locale,
    ) {}
}
