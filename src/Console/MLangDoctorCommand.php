<?php

namespace Upon\Mlang\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Upon\Mlang\Contracts\MlangContractInterface;
use Upon\Mlang\Columns\AddRowIdColumn;
use Upon\Mlang\Helpers\LanguageHelper;
use Upon\Mlang\Helpers\RowIdHelper;
use Upon\Mlang\Models\Traits\MlangTrait;

/**
 * Health check for every configured translatable model.
 *
 * Reports configuration problems (missing class / trait / table / columns) and
 * data problems (orphan rows without row_id, rows in unconfigured locales,
 * duplicate locale rows, coverage gaps). Exit code 1 when anything is wrong.
 */
class MLangDoctorCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'mlang:doctor
                            {--fix : Repair orphan rows by setting row_id = id}
                            {--json : Output the report as JSON}';

    /**
     * @var string
     */
    protected $description = 'Check translatable models and their data for problems';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $report = $this->buildReport((bool) $this->option('fix'));

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT));
        } else {
            $this->render($report);
        }

        return $report['healthy'] ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Build the full report for all configured models.
     *
     * @param bool $fix
     * @return array
     */
    public function buildReport(bool $fix = false): array
    {
        $languages = LanguageHelper::getConfiguredLanguages();
        $models = [];
        $healthy = true;

        foreach (LanguageHelper::getConfiguredModels() as $class) {
            $entry = $this->checkModel($class, $languages, $fix);
            $healthy = $healthy && empty($entry['problems']);
            $models[$class] = $entry;
        }

        return [
            'healthy' => $healthy,
            'languages' => $languages,
            'fallback_language' => LanguageHelper::getFallbackLanguage(),
            'models' => $models,
        ];
    }

    /**
     * Run all checks for a single model class.
     *
     * @param string   $class
     * @param string[] $languages
     * @param bool     $fix
     * @return array
     */
    protected function checkModel(string $class, array $languages, bool $fix): array
    {
        $problems = [];

        if (!class_exists($class)) {
            return ['problems' => ["Class {$class} does not exist"]];
        }

        if (!in_array(MlangTrait::class, class_uses_recursive($class), true)) {
            $problems[] = 'Does not use ' . MlangTrait::class;
        }

        if (!is_subclass_of($class, MlangContractInterface::class)) {
            $problems[] = 'Does not implement ' . MlangContractInterface::class;
        }

        $table = (new $class)->getTable();

        if (!Schema::hasTable($table)) {
            $problems[] = "Table '{$table}' does not exist";
            return ['table' => $table, 'problems' => $problems];
        }

        if (!Schema::hasColumns($table, ['row_id', 'iso'])) {
            $problems[] = "Table '{$table}' is missing the row_id/iso columns (run mlang:migrate)";
            return ['table' => $table, 'problems' => $problems];
        }

        $isInteger = AddRowIdColumn::isIntegerColumn($table, 'row_id');

        if (RowIdHelper::isGenerated() && $isInteger) {
            $problems[] = "row_id_type is '" . RowIdHelper::type() . "' but the column is still integer (run mlang:migrate --convert-row-id)";
        } elseif (!RowIdHelper::isGenerated() && !$isInteger) {
            $problems[] = "row_id column is " . Schema::getColumnType($table, 'row_id') . " but row_id_type is 'id' (set row_id_type to ulid/uuid)";
        }

        $orphans = DB::table($table)->whereNull('row_id')->count();
        $fixed = 0;

        if ($orphans > 0 && $fix) {
            $fixed = $isInteger
                ? DB::table($table)->whereNull('row_id')->update(['row_id' => DB::raw('id')])
                : $this->fixGeneratedOrphans($table);
            $orphans -= $fixed;
        }

        if (config('mlang.unique_row_locale', true)
            && !in_array(AddRowIdColumn::UNIQUE_INDEX, array_column(Schema::getIndexes($table), 'name'), true)) {
            $problems[] = "No (row_id, iso) unique index on '{$table}' (run mlang:migrate once duplicates are resolved)";
        }

        if ($orphans > 0) {
            $problems[] = "{$orphans} rows have no row_id (use --fix)";
        }

        $unknownIso = DB::table($table)
            ->where(fn ($q) => $q->whereNull('iso')->orWhereNotIn('iso', $languages))
            ->count();

        if ($unknownIso > 0) {
            $problems[] = "{$unknownIso} rows have an iso that is not in mlang.languages";
        }

        $duplicates = DB::table($table)
            ->select('row_id', 'iso')
            ->whereNotNull('row_id')
            ->groupBy('row_id', 'iso')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();

        if ($duplicates > 0) {
            $problems[] = "{$duplicates} (row_id, iso) pairs exist more than once";
        }

        $unique = DB::table($table)->whereNotNull('row_id')->distinct()->count('row_id');
        $perLocale = [];

        foreach ($languages as $iso) {
            $count = DB::table($table)->where('iso', $iso)->count();
            $perLocale[$iso] = ['rows' => $count, 'missing' => max(0, $unique - $count)];
        }

        $coverage = $unique === 0 ? 100.0 : round(
            array_sum(array_column($perLocale, 'rows')) / ($unique * count($languages)) * 100,
            2
        );

        return [
            'table' => $table,
            'unique_records' => $unique,
            'coverage' => $coverage,
            'locales' => $perLocale,
            'orphans_fixed' => $fixed,
            'legacy_rows' => RowIdHelper::tableHasLegacyColumn($table)
                ? DB::table($table)->whereNotNull(RowIdHelper::LEGACY_COLUMN)->count()
                : 0,
            'problems' => $problems,
        ];
    }

    /**
     * Give each orphan row its own generated row_id.
     *
     * @param string $table
     * @return int
     */
    protected function fixGeneratedOrphans(string $table): int
    {
        $fixed = 0;

        foreach (DB::table($table)->whereNull('row_id')->pluck('id') as $id) {
            DB::table($table)->where('id', $id)->update([
                'row_id' => RowIdHelper::type() === RowIdHelper::TYPE_UUID ? (string) \Illuminate\Support\Str::uuid() : (string) \Illuminate\Support\Str::ulid(),
            ]);
            $fixed++;
        }

        return $fixed;
    }

    /**
     * Print a human-readable report.
     *
     * @param array $report
     * @return void
     */
    protected function render(array $report): void
    {
        $this->info('Languages: ' . implode(', ', $report['languages']) . " (fallback: {$report['fallback_language']})");

        if (empty($report['models'])) {
            $this->warn('No models configured in mlang.models.');
            return;
        }

        foreach ($report['models'] as $class => $entry) {
            $this->newLine();
            $this->line("<comment>{$class}</comment>" . (isset($entry['table']) ? " ({$entry['table']})" : ''));

            if (isset($entry['coverage'])) {
                $this->line("  Records: {$entry['unique_records']}  Coverage: {$entry['coverage']}%");

                foreach ($entry['locales'] as $iso => $stats) {
                    $this->line(sprintf('  %-6s %5d rows  %5d missing', $iso, $stats['rows'], $stats['missing']));
                }

                if ($entry['orphans_fixed'] > 0) {
                    $this->info("  Fixed {$entry['orphans_fixed']} orphan rows");
                }
            }

            foreach ($entry['problems'] as $problem) {
                $this->error("  ✗ {$problem}");
            }

            if (empty($entry['problems'])) {
                $this->info('  ✓ OK');
            }
        }

        $this->newLine();
        $report['healthy'] ? $this->info('All checks passed.') : $this->error('Problems found.');
    }
}
