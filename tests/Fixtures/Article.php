<?php

namespace Upon\Mlang\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Upon\Mlang\Contracts\MlangContractInterface;
use Upon\Mlang\Models\Traits\MlangTrait;

/**
 * Fixture that declares translatable attributes (shares the `products` table).
 */
class Article extends Model implements MlangContractInterface
{
    use MlangTrait;

    protected $table = 'products';

    protected $guarded = [];

    protected array $translatable = ['name', 'description'];
}
