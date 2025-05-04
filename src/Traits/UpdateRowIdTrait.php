<?php

namespace Upon\Mlang\Traits;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Upon\Mlang\Jobs\MlangCreateJob;

trait UpdateRowIdTrait
{
    use MLangColumnCheckTrait;

    /**
     * Update the row_id of a model and potentially trigger translation generation
     *
     * @param mixed $model The model instance to update
     * @return bool Whether the operation succeeded
     */
    public function updateRowId($model): bool
    {
        try {
            // Check if required columns exist in the model's table
            if (!$this->hasRequiredColumns($model)) {
                return false;
            }

            // If row_id is already set, nothing to do
            if ($model->row_id) {
                return true;
            }

            // Determine if model uses ULIDs
            $modelTraits = class_uses_recursive($model);
            $hasUlid = in_array(HasUlids::class, $modelTraits, true);

            // Update the row_id to match the id
            $model->row_id = $model->id;

            // Set initial language if not set
            if (empty($model->iso)) {
                $model->iso = config('mlang.fallback_language', 'en');
            }

            // Save the model
            $saved = $model->save();

            // If auto-generation is enabled and not running in console, dispatch the create job
            if ($saved && config('mlang.auto_generate') && !app()->runningInConsole()) {
                MlangCreateJob::dispatch($model, $hasUlid);
            }

            return $saved;
        } catch (\Throwable $e) {
            // Log the error if a logger is available
            if (app()->has('log')) {
                app('log')->error('Error updating row_id: ' . $e->getMessage());
            }

            return false;
        }
    }
}
