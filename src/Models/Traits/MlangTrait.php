<?php

namespace Upon\Mlang\Models\Traits;

use Illuminate\Database\Eloquent\Model;
use Upon\Mlang\Observers\MlangObserver;

trait MlangTrait
{

    public static function bootMlangTrait()
    {
        (new static)->registerObserver(MlangObserver::class);

    }

//    /**
//     * @param mixed $value
//     * @param null  $field
//     * @return \never
//     */
//    public function resolveRouteBinding($value, $field = null)
//    {
//        return $this->where([['row_id', (int)$value], ['iso', app()->getLocale()]])->first() ??
//            abort(404, __("Model not Found."));
//    }
//
//    /**
//     * @return string
//     */
//    public function getKeyName()
//    {
//        return 'row_id';
//    }

    /**
     * Get the value of the model's primary key.
     *
     * @param $value
     * @return mixed
     */
    public function getIdAttribute($value): mixed
    {
        if ($this?->row_id){
            return $this->getAttribute('row_id');
        }

        return $value;
    }

}
