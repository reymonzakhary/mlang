<?php

namespace Upon\Mlang\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Contracts\Database\Query\Expression;
use Upon\Mlang\Observers\MlangObserver;

trait MlangTrait
{
    /**
     * The column is the key what used for route middle binding.
     * You can change it to the default id or other column.
     * Default is row id to switch the language directly.
     *
     * @var string
     */
    public string $column = 'row_id';

    /**
     * @var string[]
     */
    private $fill = ['iso','row_id'];

    /**
     * The model name give the current used model name.
     * @var string|null
     */
    protected ?string $model_name = null;

    /**
     * Creates a new instance of the model.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        $this->hasUlid();

        // Only add MLang columns to fillable if auto_generate is enabled
        if (config('mlang.auto_generate', false)) {
            $this->fillable = array_merge($this->fillable, $this->fill);
        }

        parent::__construct($attributes);

        // Set model name
        $this->getModelName();
    }

    /**
     * Get the current model name
     *
     * @return string
     */
    public function getModelName(): string
    {
        if (!$this->model_name) {
            $model = explode('\\', get_class($this));
            $this->model_name = array_pop($model);
        }

        return $this->model_name;
    }

    /**
     * Retrieve the model for a bound value.
     *
     * @param mixed $value
     * @param mixed $field
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function resolveRouteBinding($value, $field = null)
    {
        // Only use MLang columns if auto_generate is enabled
        if (config('mlang.auto_generate', false)) {
            return $this->where([[$this->column, $value], ['iso', app()->getLocale()]])->first() ??
                abort(404, __("Not Found -- There is no {$this->getModelName()} found"));
        }

        // Use standard ID for binding otherwise
        return $this->where('id', $value)->first() ??
            abort(404, __("Not Found -- There is no {$this->getModelName()} found"));
    }

    /**
     * Get a model with where query
     *
     * @param array|string|\Closure|Expression $attributes
     * @return static
     */
    public function scopeTrWhere(
        array|string|\Closure|Expression $attributes = []
    ): static
    {
        $func_get_args = func_get_args();

        // Only apply MLang-specific logic if auto_generate is enabled
        if (config('mlang.auto_generate', false)) {
            array_walk_recursive($func_get_args, static fn(&$v) => $v !== 'id'?:$v = 'row_id');
            $this->query->where(...$func_get_args)
                ->where('iso', app()->getLocale());
        } else {
            // Use standard query otherwise
            $this->query->where(...$func_get_args);
        }

        return $this;
    }

    /**
     * Find a model by its primary key.
     *
     * @param Builder $builder
     * @param string|int $id
     * @param string|null $iso
     * @return Model|null
     */
    public function scopeTrFind(
        Builder $builder,
        string|int $id,
        string $iso = null
    ): Model|null
    {
        // Only use MLang columns if auto_generate is enabled
        if (config('mlang.auto_generate', false)) {
            $iso = $iso ?? app()->getLocale();
            return $builder->where('iso', '=', $iso)->where("row_id", '=', $id)->first();
        }

        // Use standard find otherwise
        return $builder->where('id', '=', $id)->first();
    }

    /**
     * @return void
     */
    protected function hasUlid(): void
    {
        !in_array(HasUlids::class, class_uses_recursive($this), true)?:
            $this->fill = array_merge($this->fill, ['id']);
    }

    /**
     * Boot the trait - OBSERVER DISABLED TO PREVENT MEMORY ISSUES
     */
    public static function bootMlangTrait()
    {
        if(config('mlang.auto_generate', false)){
            static::observe(MlangObserver::class);
        }
        // Observer completely disabled to prevent memory issues
    }

    /**
     * Get the value of the model's primary key.
     *
     * @param $value
     * @return mixed
     */
    public function getIdAttribute($value): mixed
    {
        // Only override ID attribute if MLang features are enabled and row_id exists
        if (config('mlang.auto_generate', false) && !app()->runningInConsole() && $this?->row_id) {
            return $this->getAttribute('row_id');
        }
        return $value;
    }
}
