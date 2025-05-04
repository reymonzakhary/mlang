<?php

namespace Upon\Mlang\Traits;

use Illuminate\Support\Facades\Schema;

trait MLangColumnCheckTrait
{
    /**
     * Check if the required MLang columns exist in the model's table
     *
     * @param mixed $model The model to check
     * @return bool Whether the required columns exist
     */
    protected function hasRequiredColumns($model): bool
    {
        try {
            $table = $model->getTable();

            return Schema::hasTable($table) &&
                Schema::hasColumn($table, 'row_id') &&
                Schema::hasColumn($table, 'iso');
        } catch (\Throwable $e) {
            // If there's an error checking columns, assume they don't exist
            if (app()->has('log')) {
                app('log')->debug('Error checking MLang columns: ' . $e->getMessage());
            }
            return false;
        }
    }
}
