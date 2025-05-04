<?php

namespace Upon\Mlang\Facades;

use Illuminate\Support\Facades\Facade;

class MLang extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'mlang';
    }

    /**
     * Set the model to work with
     *
     * @param object|string $model Either a model class name or instance
     * @return \Upon\Mlang\Mlang
     */
    public static function forModel(object|string $model): \Upon\Mlang\Mlang
    {
        $instance = static::getFacadeRoot();
        $instance->setCurrentModel($model);

        return $instance;
    }
}
