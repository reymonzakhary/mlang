<?php

namespace Upon\Mlang\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Upon\Mlang\Contracts\MlangContractInterface;
use Upon\Mlang\Models\Traits\MlangTrait;

class MlangModel extends Model implements MlangContractInterface
{
    use MlangTrait;

    private $fill = ['iso'];

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table;

    /**
     * The models list what used for migrating the new columns to it.
     * @var array|mixed
     */
    protected array $models = [];

    /**
     * Creates a new instance of the model.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        $this->fillable = array_merge($this->fillable, $this->fill);
        parent::__construct($attributes);
        $this->table = $this->getTable();
        $this->models = Config::get('mlang.models');
    }




    /**
     * Collect all tables name from the giving models.
     *
     * @return array
     */
    public function getTableNames(): array
    {
        return collect($this->models)->map(fn($model) => app($model)->table)->toArray();
    }

}
