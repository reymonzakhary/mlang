<?php

namespace Upon\Mlang\Facades;

use Illuminate\Support\Facades\Facade;

class MlangFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'm-lang';
    }
}
