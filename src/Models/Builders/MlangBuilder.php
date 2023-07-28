<?php

namespace Upon\Mlang\Models\Builders;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MlangBuilder  extends Builder
{
    /**
     * Get a model with where query
     *
     * @param array $attributes
     * @return \Illuminate\Database\Query\Builder
     */
    public function trWhere(
        array $attributes = []
    ): \Illuminate\Database\Query\Builder
    {
        $func_get_args = func_get_args();
        array_walk_recursive($func_get_args, static fn(&$v) => $v !== 'id'?:$v = 'row_id');
        return $this->query->where(...$func_get_args);
    }

    /**
     * Find a model by its primary key.
     *
     * @param string|int $id
     * @param null $iso
     * @return Model|null
     */
    public function trfind(
        string|int $id,
        $iso = null
    ): Model|null
    {
        $iso = $iso ?? app()->getLocale();
        return $this->where('iso', '=', $iso)->where("row_id", '=', $id)->first();
    }

}
