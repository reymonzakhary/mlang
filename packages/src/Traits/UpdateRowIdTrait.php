<?php

namespace Upon\Mlang\Traits;

use Upon\Mlang\Events\MlangCreateEvent;
use Upon\Mlang\Jobs\MlangCreateJob;

trait UpdateRowIdTrait
{
    public function updateRowId($model){
        if(!$model->row_id) {
            $model->row_id = $model->id;
        }
        $model->save();

        if(config('mlang.auto_generate')) {
           MlangCreateJob::dispatch($model);
        }
    }


}
