<?php

namespace Upon\Mlang\Traits;

trait UpdateBaseIdTrait
{
    /**
     *
     * @param $model
     */
    public function updateBaseId($model): void
    {
        $m = $model;
        if(
            $m->parent?->id === $m->id
        ) {
            $m->parent_id = null;
        }

        while(
            ($m->parent !== null && optional($m->parent)->id !== $m->id) ||
            optional($m->parent)->id !== null
        ) {
            $m = $m->parent;
        }

        if(!$model->base_id) {
            $model->base_id = $m->id;
        }

        $model->save();
    }
}
