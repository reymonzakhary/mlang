<?php

namespace Upon\Mlang\Models\Builders;

use Illuminate\Database\Eloquent\Builder;

class MlangBuilder  extends Builder
{

    public function addTranslation(
        array $attributes = [],
        $iso = null
    )
    {

    }
    /**
     * Get a model with where query
     *
     * @param array $attributes
     * @return \Illuminate\Database\Query\Builder
     */
    public function whereLanguage(
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
     * @return MlangBuilder
     */
    public function findByLanguage(string|int $id, $iso = null)
    {
        $iso = $iso ?? app()->getLocale();
        return $this->where('iso', '=', $iso)->where("row_id", '=', $id)->first();
    }

}
