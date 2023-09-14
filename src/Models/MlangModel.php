<?php

namespace Upon\Mlang\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Support\Facades\Config;
use Upon\Mlang\Contracts\MlangContractInterface;
use Upon\Mlang\Models\Traits\MlangTrait;

class MlangModel extends Model implements MlangContractInterface
{
    use MlangTrait;

    private $fill = ['iso','row_id'];

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

    /**
     * return all models full namespaces.
     * @return array
     */
    public function getModels(): array
    {
        return $this->models;
    }

    /**
     * Get a model with where query
     *
     * @param array|string|\Closure|Expression $attributes
     * @return MlangModel
     */
    public function trWhere(
        array|string|\Closure|Expression $attributes = []
    ): MlangModel
    {
        $func_get_args = func_get_args();
        array_walk_recursive($func_get_args, static fn(&$v) => $v !== 'id'?:$v = 'row_id');

        $this->query->where(...$func_get_args)
            ->where('iso', app()->getLocale());

        return $this;
    }

    /**
     * Find a model by its primary key.
     *
     * @param string|int $id
     * @param null $iso
     * @return Model|null
     */
    public function trFind(
        string|int $id,
        $iso = null
    ): Model|null
    {
        $iso = $iso ?? app()->getLocale();
        return $this->where('iso', '=', $iso)->where("row_id", '=', $id)->first();
    }
}
