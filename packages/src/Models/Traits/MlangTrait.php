<?php

namespace Upon\Mlang\Models\Traits;

trait MlangTrait
{

    public static function bootMlangTrait()
    {
        dd(\Upon\Mlang\Columns\AddRowIdColumn::up((new self)->getTable()));
        dd((new self)->getTable());
        dd('booted');
    }

}
