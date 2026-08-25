<?php

namespace Upon\Mlang\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Upon\Mlang\Contracts\MlangContractInterface;
use Upon\Mlang\Models\Traits\MlangTrait;

class Product extends Model implements MlangContractInterface
{
    use MlangTrait;

    protected $guarded = [];
}
