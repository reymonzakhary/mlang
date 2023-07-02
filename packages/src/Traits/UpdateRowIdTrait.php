<?php

namespace Upon\Mlang\Traits;

trait UpdateRowIdTrait
{
    public function updateRowId($model){
        if(!$model->row_id) {
            $model->row_id = $model->id;
        }
        $model->save();
    }
}
