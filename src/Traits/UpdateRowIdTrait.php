<?php

namespace Upon\Mlang\Traits;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Upon\Mlang\Helpers\RowIdHelper;
use Upon\Mlang\Jobs\MlangCreateJob;

trait UpdateRowIdTrait
{
    use MLangColumnCheckTrait;

    /**
     * Object ids of models whose row_id was generated up-front in `creating`
     * and that still need their translations dispatched after `created`.
     *
     * @var array<int, true>
     */
    protected static array $pendingSourceRows = [];

    /**
     * Assign a generated (ulid/uuid) row_id before insert.
     * No-op for the legacy integer type, which is copied from the id after insert.
     *
     * @param mixed $model
     * @return void
     */
    public function assignGeneratedRowId($model): void
    {
        if (!RowIdHelper::isGenerated() || !empty($model->row_id)) {
            return;
        }

        $model->row_id = RowIdHelper::generate($model);
        static::$pendingSourceRows[spl_object_id($model)] = true;
    }

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

            // Determine if model uses ULIDs
            $modelTraits = class_uses_recursive($model);
            $hasUlid = in_array(HasUlids::class, $modelTraits, true);

            // row_id generated in `creating`: only the translations are still pending
            if (isset(static::$pendingSourceRows[spl_object_id($model)])) {
                unset(static::$pendingSourceRows[spl_object_id($model)]);
                $this->dispatchTranslations($model, $hasUlid);
                return true;
            }

            // If row_id is already set, nothing to do
            if ($model->row_id) {
                return true;
            }

            // Update the row_id to match the id
            $model->row_id = $model->id;

            // Set initial language if not set
            if (empty($model->iso)) {
                $model->iso = config('mlang.fallback_language', 'en');
            }

            // Save the model
            $saved = $model->save();

            if ($saved) {
                $this->dispatchTranslations($model, $hasUlid);
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

    /**
     * Queue creation of the other locale rows for a freshly created source row.
     *
     * @param mixed $model
     * @param bool  $hasUlid
     * @return void
     */
    protected function dispatchTranslations($model, bool $hasUlid): void
    {
        // If auto-generation is enabled and not running in console, dispatch the create job
        if (config('mlang.auto_generate') && !app()->runningInConsole()) {
            MlangCreateJob::dispatch($model, $hasUlid);
        }
    }
}
