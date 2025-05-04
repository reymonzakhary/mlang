<?php

namespace Upon\Mlang\Jobs;

use Doctrine\DBAL\Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MlangCreateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Create a new job instance.
     *
     * @param Model $model The model to create translations for
     * @param bool $ulid Whether the model uses ULIDs
     * @param array|null $languages Specific languages to create (null for all configured languages)
     */
    public function __construct(
        public Model $model,
        public bool $ulid = false,
        public ?array $languages = null
    ){}

    /**
     * Execute the job.
     *
     * @return void
     * @throws Exception
     */
    public function handle(): void
    {
        try {
            // Check if we have row_id to work with
            if (empty($this->model->row_id)) {
                Log::warning('MlangCreateJob: Model has no row_id set', [
                    'model' => get_class($this->model),
                    'id' => $this->model->id
                ]);
                return;
            }

            // Get languages to process
            $languages = $this->languages ?? Config::get('mlang.languages', []);
            if (empty($languages)) {
                Log::warning('MlangCreateJob: No languages configured');
                return;
            }

            $row_id = $this->model->row_id;
            $modelClass = get_class($this->model);
            $table = $this->model->getTable();

            // Get unique indexes
            $uniqueIndexes = $this->getUniqueIndexes($table);

            // Create translations for each language
            collect($languages)
                ->reject(fn($language) => $this->model->iso === $language)
                ->each(function($language) use ($row_id, $uniqueIndexes, $modelClass) {
                    try {
                        // Get base model attributes
                        $row = $this->model->toArray();


                        // Set language and row_id
                        $row = array_merge($row, ['iso' => $language, 'row_id' => $row_id]);

                        // Handle primary key
                        unset($row['id']);
                        if ($this->ulid) {
                            $row = array_merge($row, ['id' => $this->generateUlid()]);
                        }

                        // Handle unique fields
                        $this->handleUniqueFields($row, $uniqueIndexes);

                        // Create the translated record
                        $modelClass::create($row);

                        Log::info("MlangCreateJob: Created translation", [
                            'model' => $modelClass,
                            'language' => $language,
                            'row_id' => $row_id
                        ]);
                    } catch (\Throwable $e) {
                        Log::error("MlangCreateJob: Failed to create translation for language {$language}", [
                            'model' => $modelClass,
                            'row_id' => $row_id,
                            'error' => $e->getMessage()
                        ]);
                    }
                });
        } catch (\Throwable $e) {
            Log::error('MlangCreateJob: Failed to process model', [
                'model' => get_class($this->model),
                'id' => $this->model->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Re-throw for queue to handle
            throw $e;
        }
    }

    /**
     * Get unique indexes for a table in a database-agnostic way
     *
     * @param string $table
     * @return array
     */
    protected function getUniqueIndexes(string $table): array
    {
        try {
            $driverName = DB::connection()->getDriverName();

            if ($driverName === 'mysql' || $driverName === 'mariadb') {
                $indexes = DB::select("SHOW INDEXES FROM {$table} WHERE Non_unique = 0 AND Key_name != 'PRIMARY'");
                return collect($indexes)
                    ->pluck('Column_name')
                    ->unique()
                    ->toArray();
            } elseif ($driverName === 'pgsql') {
                $indexes = DB::select("
                SELECT a.attname
                FROM pg_index i
                JOIN pg_attribute a ON a.attrelid = i.indrelid AND a.attnum = ANY(i.indkey)
                WHERE i.indrelid = '{$table}'::regclass
                AND i.indisunique AND NOT i.indisprimary
            ");
                return collect($indexes)->pluck('attname')->toArray();
            } elseif ($driverName === 'sqlite') {
                // For SQLite, get the unique indexes
                $indexes = DB::select("
                SELECT name, sql
                FROM sqlite_master
                WHERE type = 'index'
                AND sql LIKE '%UNIQUE%'
                AND tbl_name = '{$table}'
            ");

                // SQLite is more complex to parse, so for now we'll just return empty
                // For a production implementation, you'd want to parse the SQL
                return [];
            }

            // For other database types, return empty for now
            return [];
        } catch (\Throwable $e) {
            Log::warning("MlangCreateJob: Failed to get unique indexes for table {$table}", [
                'error' => $e->getMessage(),
                'driver' => DB::connection()->getDriverName()
            ]);

            return [];
        }
    }

    /**
     * Handle unique fields in the data
     *
     * @param array &$row
     * @param array $uniqueIndexes
     * @return void
     */
    protected function handleUniqueFields(array &$row, array $uniqueIndexes): void
    {
        foreach ($uniqueIndexes as $key) {
            // Check if the key exists in the row data
            if (array_key_exists($key, $row)) {
                // Skip null values
                if ($row[$key] === null) {
                    continue;
                }

                $suffix = '_' . Str::random(3);

                // Handle various types of unique fields
                if (is_string($row[$key])) {
                    // For strings, append the suffix
                    $row[$key] = $row[$key] . $suffix;
                } elseif (is_numeric($row[$key])) {
                    // For numeric values, convert to string and append
                    $row[$key] = (string)$row[$key] . $suffix;
                } else {
                    // For other types, try to handle as string if possible
                    try {
                        $row[$key] = (string)$row[$key] . $suffix;
                    } catch (\Throwable $e) {
                        // If conversion fails, log warning and leave unchanged
                        Log::warning("MlangCreateJob: Could not handle unique field of type " . gettype($row[$key]), [
                            'field' => $key,
                            'type' => gettype($row[$key])
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Generate a new ULID for the model.
     *
     * @return string
     */
    protected function generateUlid(): string
    {
        return strtolower((string) Str::ulid());
    }
}
