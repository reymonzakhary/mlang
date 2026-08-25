<?php

namespace Upon\Mlang\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired after non-translatable ("shared") attributes were propagated from one
 * translation row to its siblings.
 */
class SharedAttributesSynced
{
    use Dispatchable;

    /**
     * @param Model $source     The row that was saved and triggered the sync.
     * @param array $attributes The attribute => value pairs that were propagated.
     * @param int   $affected   Number of sibling rows updated.
     */
    public function __construct(
        public Model $source,
        public array $attributes,
        public int $affected,
    ) {}
}
