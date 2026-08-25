<?php

namespace Upon\Mlang\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Contracts\Database\Query\Expression;
use Upon\Mlang\Events\TranslationMissing;
use Upon\Mlang\Helpers\LanguageHelper;
use Upon\Mlang\Helpers\RowIdHelper;
use Upon\Mlang\Models\Concerns\HasTranslations;
use Upon\Mlang\Observers\MlangObserver;

trait MlangTrait
{
    use HasTranslations;

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

        // Make the MLang columns mass-assignable when auto_generate is enabled.
        // A fully unguarded model ($guarded = []) is left untouched: setting
        // $fillable on it would restrict mass assignment to iso/row_id only.
        if (config('mlang.auto_generate', false) && !($this->fillable === [] && $this->guarded === [])) {
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
            $column = $this->column === 'row_id' ? RowIdHelper::lookupColumn($this->getTable(), $value) : $this->column;

            return $this->where([[$column, $value], ['iso', app()->getLocale()]])->first() ??
                abort(404, __("Not Found -- There is no {$this->getModelName()} found"));
        }

        // Use standard ID for binding otherwise
        return $this->where('id', $value)->first() ??
            abort(404, __("Not Found -- There is no {$this->getModelName()} found"));
    }

    /**
     * Get a model with where query
     *
     * @param Builder $builder
     * @param array|string|\Closure|Expression $attributes
     * @param mixed ...$args
     * @return Builder
     */
    public function scopeTrWhere(
        Builder $builder,
        array|string|\Closure|Expression $attributes = [],
        mixed ...$args
    ): Builder
    {
        $wheres = [$attributes, ...$args];

        // Only apply MLang-specific logic if auto_generate is enabled
        if (config('mlang.auto_generate', false)) {
            array_walk_recursive($wheres, static fn(&$v) => $v !== 'id' ?: $v = 'row_id');

            $builder->where(...$wheres);

            return config('mlang.fallback_on_query', false)
                ? $this->scopeWithFallback($builder)
                : $this->scopeInLocale($builder);
        }

        return $builder->where(...$wheres);
    }

    /**
     * Find a record by its row_id in the given (or current) locale.
     *
     * Implemented as a static method rather than a scope because Eloquent's
     * callScope() replaces a null scope result with the Builder, which made the
     * old scope version impossible to null-check.
     *
     * @param string|int  $id
     * @param string|null $iso
     * @param bool|null   $fallback Override mlang.fallback_on_query for this call.
     * @return static|null
     */
    public static function trFind(string|int $id, ?string $iso = null, ?bool $fallback = null): ?static
    {
        $query = static::query();

        // Only use MLang columns if auto_generate is enabled
        if (!config('mlang.auto_generate', false)) {
            return $query->where('id', '=', $id)->first();
        }

        $iso = $iso ?? app()->getLocale();
        $fallback = $fallback ?? (bool) config('mlang.fallback_on_query', false);

        $column = RowIdHelper::lookupColumn((new static)->getTable(), $id);
        $found = (clone $query)->where($column, '=', $id)->where('iso', '=', $iso)->first();

        if ($found || !$fallback) {
            return $found;
        }

        TranslationMissing::dispatch(static::class, $id, $iso);

        return $query->where($column, '=', $id)
            ->where('iso', '=', LanguageHelper::getFallbackLanguage())
            ->first();
    }

    /**
     * Builder-friendly counterpart of trFind(): constrain a query to one record
     * (by row_id, or legacy_row_id for integer values after a conversion) in a locale.
     *
     *   Product::whereRow(1)->first();
     *   Product::query()->whereRow($id, 'fr')->with('translations')->first();
     *
     * @param Builder     $builder
     * @param string|int  $id
     * @param string|null $iso
     * @return Builder
     */
    public function scopeWhereRow(Builder $builder, string|int $id, ?string $iso = null): Builder
    {
        if (!config('mlang.auto_generate', false)) {
            return $builder->where($builder->qualifyColumn('id'), $id);
        }

        return $builder
            ->where($builder->qualifyColumn(RowIdHelper::lookupColumn($this->getTable(), $id)), $id)
            ->where($builder->qualifyColumn('iso'), $iso ?? app()->getLocale());
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
     * Boot the trait and register the MLang observer.
     *
     * The observer is registered event-by-event instead of via static::observe(),
     * because observe() instantiates the model (`new static`) while it is still
     * booting, which Laravel 13 rejects with a LogicException.
     */
    public static function bootMlangTrait(): void
    {
        if (!config('mlang.auto_generate', false)) {
            return;
        }

        $observer = app(MlangObserver::class);

        foreach (['creating', 'created', 'updating', 'updated', 'deleting', 'deleted', 'saving', 'saved'] as $event) {
            if (method_exists($observer, $event)) {
                static::registerModelEvent($event, [$observer, $event]);
            }
        }
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
