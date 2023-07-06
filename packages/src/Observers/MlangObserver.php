<?php

namespace Upon\Mlang\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Upon\Mlang\Traits\UpdateBaseIdTrait;
use Upon\Mlang\Traits\UpdateRowIdTrait;

class MlangObserver
{
    use UpdateRowIdTrait;

    public function creating(Model $model)
    {
        if(!$model?->iso) {
            $model->iso = App::getLocale();
        }
    }

    /**
     * Handle the Model "created" event.
     */
    public function created(Model $model): void
    {
        $this->updateRowId($model);
    }

    /**
     * Handle the Model "updated" event.
     */
    public function updated(Model $model): void
    {
        //
    }

    /**
     * Handle the Model "deleted" event.
     */
    public function deleted(Model $model): void
    {
        //
    }

    /**
     * Handle the Model "restored" event.
     */
    public function restored(Model $model): void
    {
        //
    }

    /**
     * Handle the Model "force deleted" event.
     */
    public function forceDeleted(Model $model): void
    {
        //
    }
}
