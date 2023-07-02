<?php

namespace Upon\Mlang\Models\Traits;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\App;
use Upon\Mlang\Observers\MlangObserver;

trait MlangTrait
{

    public static function bootMlangTrait()
    {
        (new static)->registerObserver(MlangObserver::class);
    }


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
