<?php

namespace Upon\Mlang\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired when a record is requested in a locale that has no row yet.
 * Listen to this to trigger machine translation, queue a job, or notify editors.
 */
class TranslationMissing
{
    use Dispatchable;

    /**
     * @param string     $model  Fully-qualified model class.
     * @param int|string $rowId  The row_id that groups the translations.
     * @param string     $locale The locale that was requested but not found.
     */
    public function __construct(
        public string $model,
        public int|string $rowId,
        public string $locale,
    ) {}
}
