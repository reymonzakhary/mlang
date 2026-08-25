<?php

namespace Upon\Mlang\Console;

use Illuminate\Console\Command;
use Upon\Mlang\Columns\AddRowIdColumn;
use Upon\Mlang\Facades\MLang;
use Upon\Mlang\Helpers\RowIdHelper;

class MLangMigrateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mlang:migrate
                            {--rollback : Roll back MLang columns}
                            {--table= : Specific table to migrate or rollback}
                            {--convert-row-id : Convert integer row_id columns to the configured ulid/uuid type (keeps legacy_row_id)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add or remove row_id and iso columns from translatable models';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isRollback = $this->option('rollback');
        $specificTable = $this->option('table');

        if ($this->option('convert-row-id')) {
            return $this->convertRowIds($specificTable);
        }

        $this->prepare($isRollback, $specificTable);
        return 0;
    }

    /**
     * @param $isRollback
     * @param string|null $specificTable
     * @return void
     */
    public function prepare(
        $isRollback,
        ?string $specificTable = null
    ): void
    {
        $models = MLang::getModels();
        $tables = MLang::getTableNames();

        if (empty($tables)) {
            $this->info("No tables were found.");
            return;
        }

        // Filter for specific table if provided
        if ($specificTable) {
            $filteredTables = [];
            $filteredModels = [];

            foreach ($tables as $k => $table) {
                if ($table === $specificTable) {
                    $filteredTables[] = $table;
                    $filteredModels[] = $models[$k];
                    break;
                }
            }

            if (empty($filteredTables)) {
                $this->error("Table '{$specificTable}' not found in MLang models.");
                return;
            }

            $tables = $filteredTables;
            $models = $filteredModels;
        }

        if ($isRollback) {
            $this->rollbackColumnsFromTables($models, $tables);
        } else {
            $this->addColumnsToModels($models, $tables);
        }
    }

    /**
     * Convert integer row_id columns to the configured ulid/uuid type.
     *
     * @param string|null $specificTable
     * @return int
     */
    protected function convertRowIds(?string $specificTable): int
    {
        if (!RowIdHelper::isGenerated()) {
            $this->error("Set 'row_id_type' to 'ulid' or 'uuid' in config/mlang.php before converting.");
            return self::FAILURE;
        }

        $tables = $specificTable ? [$specificTable] : MLang::getTableNames();

        foreach ($tables as $table) {
            try {
                $groups = AddRowIdColumn::convert($table);
                $this->info($groups > 0
                    ? "{$table}: converted {$groups} record groups to " . RowIdHelper::type() . " row ids (integers kept in legacy_row_id)."
                    : "{$table}: already converted.");
            } catch (\Throwable $e) {
                $this->error("{$table}: {$e->getMessage()}");
                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }

    /**
     * Add MLang columns to models
     *
     * @param $models
     * @param $tables
     * @return void
     */
    private function addColumnsToModels(
        $models, $tables
    ): void
    {
        $count = 0;
        foreach ($tables as $k => $table) {
            if ($table) {
                try {
                    AddRowIdColumn::up($table, "\\".$models[$k]);
                    $this->info("Columns have been added to {$table} table.");

                    if (config('mlang.unique_row_locale', true) && ($dupes = AddRowIdColumn::duplicateCount($table)) > 0) {
                        $this->warn("  Skipped the (row_id, iso) unique index on {$table}: {$dupes} duplicate pairs exist. Clean them up (see mlang:doctor) and re-run mlang:migrate.");
                    }
                    $count++;
                } catch (\Exception $e) {
                    $this->error("Failed to add columns to {$table}: " . $e->getMessage());
                }
            }
        }

        $this->info("{$count} tables have been processed successfully.");
    }

    /**
     * Remove MLang columns from tables
     *
     * @param array $models
     * @param array $tables
     * @return void
     */
    private function rollbackColumnsFromTables(
        array $models,
        array $tables
    ): void
    {
        $count = 0;
        foreach ($tables as $k => $table) {
            if ($table) {
                try {
                    AddRowIdColumn::down($table);
                    $this->info("Columns have been removed from {$table} table.");
                    $count++;
                } catch (\Exception $e) {
                    $this->error("Failed to remove columns from {$table}: " . $e->getMessage());
                }
            }
        }

        $this->info("{$count} tables have been processed successfully.");
    }
}
