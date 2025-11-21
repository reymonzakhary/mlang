<?php

namespace Upon\Mlang;

use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Upon\Mlang\Contracts\MlangContractInterface;
use Upon\Mlang\Helpers\SecurityHelper;
use Upon\Mlang\Helpers\LanguageHelper;
use Upon\Mlang\Helpers\TranslationHelper;
use Upon\Mlang\Helpers\QueryHelper;
use InvalidArgumentException;

class MLang implements MlangContractInterface
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
        $this->models = LanguageHelper::getConfiguredModels();
        $this->getModelName();
    }

    /**
     * Set the current model to work with
     *
     * @param object|string $model
     * @return self
     * @throws BindingResolutionException
     * @throws InvalidArgumentException
     */
    public function setCurrentModel(object|string $model): self
    {
        // Validate model if it's a string
        if (is_string($model)) {
            SecurityHelper::validateModelClass($model);
        }

        $this->currentModel = $model;

        if (is_string($model) && class_exists($model)) {
            $instance = $this->app->make($model);
            $this->table = $instance->getTable();

            // Validate table name
            SecurityHelper::validateTableName($this->table);

            // Get model name from the class
            $parts = explode('\\', $model);
            $this->model_name = end($parts);
        } elseif (is_object($model)) {
            $this->table = $model->getTable();

            // Validate table name
            SecurityHelper::validateTableName($this->table);

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
            try {
                SecurityHelper::validateModelClass($model);
                return $this->app->make($model)->getTable();
            } catch (Exception $e) {
                // Skip invalid models
                return null;
            }
        })->filter()->toArray();
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
     * Get the table name associated with the model instance
     *
     * @return string|null
     * @throws BindingResolutionException
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
     * @throws InvalidArgumentException
     * @throws BindingResolutionException
     */
    public function migrate(?string $table = null): self
    {
        $params = [];

        // If table not provided but we have a current model, use its table
        if ($table === null && $this->currentModel !== null) {
            $table = $this->getTableName();
        }

        // Validate table name if provided
        if ($table !== null) {
            SecurityHelper::validateTableName($table);
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
     * @throws InvalidArgumentException
     * @throws BindingResolutionException
     */
    public function rollback(?string $table = null): self
    {
        $params = ['--rollback' => true];

        // If table not provided but we have a current model, use its table
        if ($table === null && $this->currentModel !== null) {
            $table = $this->getTableName();
        }

        // Validate table name if provided
        if ($table !== null) {
            SecurityHelper::validateTableName($table);
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
     * @throws InvalidArgumentException
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
            // Validate locale
            SecurityHelper::validateLocale($locale);
            $params['locale'] = $locale;
        }

        // Run generate command
        Artisan::call('mlang:generate', $params);

        return $this;
    }

    /**
     * Create a record in multiple languages at once
     *
     * @param array $attributes Base attributes for the record
     * @param array|null $languages Languages to create (default: all configured)
     * @param array|null $translatedAttributes Language-specific attributes
     * @return array Created records
     * @throws BindingResolutionException
     */
    public function createMultiLanguage(
        array $attributes,
        ?array $languages = null,
        ?array $translatedAttributes = null
    ): array {
        if ($this->currentModel === null) {
            throw new InvalidArgumentException('No model set. Use forModel() first.');
        }

        $modelInstance = $this->getModelInstance();

        // Use all configured languages if not specified
        if ($languages === null) {
            $languages = LanguageHelper::getConfiguredLanguages();
        }

        // Rate limiting check
        if (!SecurityHelper::checkRateLimit('create_multi_language', 100, 1)) {
            throw new InvalidArgumentException('Rate limit exceeded for bulk operations.');
        }

        return TranslationHelper::createMultiLanguageRecord(
            $modelInstance,
            $attributes,
            $languages,
            $translatedAttributes
        );
    }

    /**
     * Get translation statistics for current model
     *
     * @return array
     * @throws InvalidArgumentException|BindingResolutionException
     */
    public function getStats(): array
    {
        if ($this->currentModel === null) {
            throw new InvalidArgumentException('No model set. Use forModel() first.');
        }

        $modelInstance = $this->getModelInstance();
        return TranslationHelper::getTranslationStats($modelInstance);
    }

    /**
     * Get all translations for a specific record by id
     *
     * @param int|string $id Record ID (not row_id)
     * @return Collection
     * @throws InvalidArgumentException
     * @throws BindingResolutionException
     */
    public function getAllTranslations(int|string $id)
    {
        if ($this->currentModel === null) {
            throw new InvalidArgumentException('No model set. Use forModel() first.');
        }

        $modelInstance = $this->getModelInstance();
        return QueryHelper::getAllTranslationsById($modelInstance, $id);
    }

    /**
     * Update all translations for a record by id
     *
     * @param int|string $id Record ID (not row_id)
     * @param array $attributes
     * @return int Number of updated records
     * @throws InvalidArgumentException|BindingResolutionException
     */
    public function updateAllTranslations(int|string $id, array $attributes): int
    {
        if ($this->currentModel === null) {
            throw new InvalidArgumentException('No model set. Use forModel() first.');
        }

        $modelInstance = $this->getModelInstance();
        return TranslationHelper::updateAllTranslationsById($modelInstance, $id, $attributes);
    }

    /**
     * Delete all translations for a record by id
     *
     * @param int|string $id Record ID (not row_id)
     * @return int Number of deleted records
     * @throws InvalidArgumentException|BindingResolutionException
     */
    public function deleteAllTranslations(int|string $id): int
    {
        if ($this->currentModel === null) {
            throw new InvalidArgumentException('No model set. Use forModel() first.');
        }

        $modelInstance = $this->getModelInstance();
        return TranslationHelper::deleteAllTranslationsById($modelInstance, $id);
    }

    /**
     * Copy a record to another language
     *
     * @param Model|int|string $sourceModelOrId Source model instance or ID
     * @param string $targetLanguage Target language code
     * @param array $overrideAttributes Optional attributes to override
     * @return Model|null
     * @throws InvalidArgumentException|BindingResolutionException
     */
    public function copyToLanguage(Model|int|string $sourceModelOrId, string $targetLanguage, array $overrideAttributes = []): ?Model
    {
        if ($this->currentModel === null) {
            throw new InvalidArgumentException('No model set. Use forModel() first.');
        }

        SecurityHelper::validateLocale($targetLanguage);
        $modelInstance = $this->getModelInstance();
        return TranslationHelper::copyToLanguage($modelInstance, $sourceModelOrId, $targetLanguage, $overrideAttributes);
    }

    /**
     * Get records with incomplete translations
     *
     * @return Collection
     * @throws InvalidArgumentException
     */
    public function getIncompleteTranslations()
    {
        if ($this->currentModel === null) {
            throw new InvalidArgumentException('No model set. Use forModel() first.');
        }

        $modelInstance = $this->getModelInstance();
        return QueryHelper::getRecordsWithIncompleteTranslations($modelInstance);
    }

    /**
     * Get translation coverage percentage
     *
     * @return float
     * @throws InvalidArgumentException
     */
    public function getCoverage(): float
    {
        if ($this->currentModel === null) {
            throw new InvalidArgumentException('No model set. Use forModel() first.');
        }

        $modelInstance = $this->getModelInstance();
        return QueryHelper::getTranslationCoverage($modelInstance);
    }
}
