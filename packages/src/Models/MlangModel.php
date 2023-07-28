<?php

namespace Upon\Mlang\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Config;
use Upon\Mlang\Contracts\MlangContractInterface;
use Upon\Mlang\Models\Builders\MlangBuilder;
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
     * @return \Illuminate\Database\Eloquent\Builder|MlangBuilder
     */
    public static function query(): \Illuminate\Database\Eloquent\Builder|MlangBuilder
    {
        return parent::query();
    }

    /**
     * @param Builder $query
     * @return Builder|MlangBuilder
     */
    #[Pure] public function newEloquentBuilder($query): Builder|MlangBuilder
    {
        return new MlangBuilder($query);
    }
}
