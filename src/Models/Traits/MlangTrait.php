<?php

namespace Upon\Mlang\Models\Traits;

use Upon\Mlang\Observers\MlangObserver;

trait MlangTrait
{

    public static function bootMlangTrait()
    {
        (new static)->registerObserver(MlangObserver::class);

    }

    /**
     * Get the value of the model's primary key.
     *
     * @param $value
     * @return mixed
     */
    public function getIdAttribute($value): mixed
    {
        if(config('mlang.auto_generate') && !app()->runningInConsole() && $this?->row_id) {
            return $this->getAttribute('row_id');
        }
        return $value;
    }

}
