<?php

namespace Upon\Mlang\Traits;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Upon\Mlang\Events\MlangCreateEvent;
use Upon\Mlang\Jobs\MlangCreateJob;

trait UpdateRowIdTrait
{
    public function updateRowId($model){

        $modelTraits = class_uses_recursive($model);
        $hasULid = in_array(HasUlids::class, $modelTraits, true);


        if(!$model->row_id) {
            $model->row_id = $model->id;
            $model->save();
            if(config('mlang.auto_generate') && !app()->runningInConsole()) {
                MlangCreateJob::dispatch($model, $hasULid);
            }
        }
    }


}
