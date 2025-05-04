<?php

namespace Upon\Mlang\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Upon\Mlang\Traits\MLangColumnCheckTrait;
use Upon\Mlang\Traits\UpdateRowIdTrait;

class MlangObserver
{
    use UpdateRowIdTrait, MLangColumnCheckTrait;

    /**
     * Handle the Model "creating" event.
     * Sets the language for new records if not already set
     *
     * @param Model $model
     * @return void
     */
    public function creating(Model $model): void
    {

        try {
            // Only proceed if the model has the required columns
            if (!$this->hasRequiredColumns($model)) {
                return;
            }

            // Set the language if not already set
            if (empty($model->iso)) {
                $model->iso = App::getLocale();
            }
        } catch (\Throwable $e) {
            $this->logError('Error in MlangObserver@creating', $e, $model);
        }
    }

    /**
     * Handle the Model "created" event.
     * Updates row_id after model creation
     *
     * @param Model $model
     * @return void
     */
    public function created(Model $model): void
    {
        try {
            // Only proceed if auto-generate is enabled or we're not in console
            if (!$this->shouldProcess()) {
                return;
            }

            // Update row_id for new model
            $this->updateRowId($model);
        } catch (\Throwable $e) {
            $this->logError('Error in MlangObserver@created', $e, $model);
        }
    }

    /**
     * Handle the Model "updated" event.
     * This can be used for custom logic when a model is updated
     *
     * @param Model $model
     * @return void
     */
    public function updated(Model $model): void
    {
        // You can add custom logic here if needed
    }

    /**
     * Handle the Model "deleted" event.
     * This can be used for custom delete-related logic
     *
     * @param Model $model
     * @return void
     */
    public function deleted(Model $model): void
    {
        // You can add custom delete logic here if needed
    }

    /**
     * Handle the Model "restored" event.
     * This fires when a soft-deleted model is restored
     *
     * @param Model $model
     * @return void
     */
    public function restored(Model $model): void
    {
        // Custom logic for when a soft-deleted model is restored
    }

    /**
     * Handle the Model "force deleted" event.
     * This fires when a model is permanently deleted
     *
     * @param Model $model
     * @return void
     */
    public function forceDeleted(Model $model): void
    {
        // Custom logic for permanent deletion
    }

    /**
     * Determine if we should process the model based on configuration
     *
     * @return bool
     */
    protected function shouldProcess(): bool
    {
        // Check if we're in console and if console observation is enabled
        $inConsole = app()->runningInConsole();
        $observeDuringConsole = Config::get('mlang.observe_during_console', false);

        // Don't process if we're in console and console observation is disabled
        if ($inConsole && !$observeDuringConsole) {
            return false;
        }

        return true;
    }

    /**
     * Log error with model information for debugging
     *
     * @param string $message
     * @param \Throwable $exception
     * @param Model $model
     * @return void
     */
    protected function logError(string $message, \Throwable $exception, Model $model): void
    {
        try {
            $modelClass = get_class($model);
            $modelId = $model->id ?? 'unknown';

            Log::error("{$message}: {$exception->getMessage()}", [
                'model' => $modelClass,
                'id' => $modelId,
                'trace' => $exception->getTraceAsString()
            ]);
        } catch (\Throwable $e) {
            // Fail silently if logging itself fails
        }
    }
}
