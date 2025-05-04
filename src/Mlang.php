<?php

namespace Upon\Mlang;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;
use Upon\Mlang\Contracts\MlangContractInterface;

class Mlang implements MlangContractInterface
{
    /**
     * Laravel application.
     *
     * @var Application
     */
    public Application $app;

    /**
     * The database table used by the model.
     *
     * @var string|null
     */
    protected ?string $table = null;

    /**
     * The models list what used for migrating the new columns to it.
     * @var array|mixed
     */
    protected array $models = [];

    /**
     * The model name give the current used model name.
     * @var string|null
     */
    protected ?string $model_name = null;

    /**
     * Current model being operated on
     *
     * @var string|object|null
     */
    protected $currentModel = null;

    /**
     * Create a new confide instance.
     *
     * @param  Application  $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
        $this->models = Config::get('mlang.models', []);
        $this->getModelName();
    }

    /**
     * Set the current model to work with
     *
     * @param object|string $model
     * @return self
     * @throws BindingResolutionException
     */
    public function setCurrentModel(object|string $model): self
    {
        $this->currentModel = $model;

        if (is_string($model) && class_exists($model)) {
            $instance = $this->app->make($model);
            $this->table = $instance->getTable();

            // Get model name from the class
            $parts = explode('\\', $model);
            $this->model_name = end($parts);
        } elseif (is_object($model)) {
            $this->table = $model->getTable();

            // Get model name from the object
            $className = get_class($model);
            $parts = explode('\\', $className);
            $this->model_name = end($parts);
        }

        return $this;
    }

    /**
     * Get the current model class name
     *
     * @return string|null
     */
    public function getCurrentModel(): ?string
    {
        if (is_string($this->currentModel)) {
            return $this->currentModel;
        } elseif (is_object($this->currentModel)) {
            return get_class($this->currentModel);
        }

        return null;
    }

    /**
     * Get a model instance
     *
     * @return object|string|null
     * @throws BindingResolutionException
     */
    public function getModelInstance(): object|string|null
    {
        if (is_string($this->currentModel)) {
            return $this->app->make($this->currentModel);
        } elseif (is_object($this->currentModel)) {
            return $this->currentModel;
        }

        return null;
    }

    /**
     * get the current namespace
     * @return string
     */
    public function getModelName(): string
    {
        if (!$this->model_name) {
            if ($this->currentModel) {
                if (is_string($this->currentModel)) {
                    $parts = explode('\\', $this->currentModel);
                    $this->model_name = end($parts);
                } elseif (is_object($this->currentModel)) {
                    $className = get_class($this->currentModel);
                    $parts = explode('\\', $className);
                    $this->model_name = end($parts);
                } else {
                    $model = explode('\\', get_class($this));
                    $this->model_name = array_pop($model);
                }
            } else {
                $model = explode('\\', get_class($this));
                $this->model_name = array_pop($model);
            }
        }

        return $this->model_name;
    }

    /**
     * Collect all tables name from the giving models.
     *
     * @return array
     */
    public function getTableNames(): array
    {
        return collect($this->models)->map(function($model) {
            return $this->app->make($model)->getTable();
        })->toArray();
    }

    /**
     * Return all models full namespaces.
     * @return array
     */
    public function getModels(): array
    {
        return $this->models;
    }

    /**
     * Get table name for the current model
     *
     * @return string|null
     */
    public function getTableName(): ?string
    {
        if ($this->table) {
            return $this->table;
        }

        $instance = $this->getModelInstance();

        if ($instance && method_exists($instance, 'getTable')) {
            $this->table = $instance->getTable();
            return $this->table;
        }

        return null;
    }

    /**
     * Run migration for current model or specific table
     *
     * @param string|null $table
     * @return self
     */
    public function migrate(?string $table = null): self
    {
        $params = [];

        // If table not provided but we have a current model, use its table
        if ($table === null && $this->currentModel !== null) {
            $table = $this->getTableName();
        }

        // Add table parameter if available
        if ($table !== null) {
            $params['--table'] = $table;
        }

        // Run migration command
        Artisan::call('mlang:migrate', $params);

        return $this;
    }

    /**
     * Rollback migration for current model or specific table
     *
     * @param string|null $table
     * @return self
     */
    public function rollback(?string $table = null): self
    {
        $params = ['--rollback' => true];

        // If table not provided but we have a current model, use its table
        if ($table === null && $this->currentModel !== null) {
            $table = $this->getTableName();
        }

        // Add table parameter if available
        if ($table !== null) {
            $params['--table'] = $table;
        }

        // Run migration rollback command
        Artisan::call('mlang:migrate', $params);

        return $this;
    }

    /**
     * Generate translations for current model or specific model
     *
     * @param string|null $model
     * @param string|null $locale
     * @return self
     */
    public function generate(?string $model = null, ?string $locale = null): self
    {
        $params = [];

        // If model not provided but we have a current model, use it
        if ($model === null && $this->currentModel !== null) {
            $model = $this->getModelName();
        }

        // Add parameters if available
        if ($model !== null) {
            $params['model'] = $model;
        }

        if ($locale !== null) {
            $params['locale'] = $locale;
        }

        // Run generate command
        Artisan::call('mlang:generate', $params);

        return $this;
    }

}
