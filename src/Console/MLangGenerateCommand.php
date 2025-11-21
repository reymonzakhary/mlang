<?php

namespace Upon\Mlang\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Upon\Mlang\Facades\MLang;
use Upon\Mlang\Helpers\TranslationHelper;

class MLangGenerateCommand extends Command
{
    /**
     * List of models
     * @var array
     */
    protected array $models = [];

    /**
     * List of languages
     * @var array
     */
    protected array $languages = [];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mlang:generate {model?} {locale?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate multilanguage records for models';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->getModels($this->argument('model'));
        $this->getLanguages($this->argument('locale'));
        $this->createMultiLanguageRecords();

        return 0; // Return success code for Laravel 10+
    }

    /**
     * Create multi-language records for selected models
     */
    private function createMultiLanguageRecords(): void
    {
        collect($this->models)->map(function($namespace) {
            /** @var Model $model */
            $model = new $namespace();
            $table = $model->getTable();

            // Check if MLang columns exist before proceeding
            if (!$this->checkRequiredColumns($table)) {
                $this->warn("Skipping {$namespace} - Required MLang columns don't exist in table {$table}");
                return;
            }

            $count = 0;
            $defaultLocale = Config::get('app.locale');

            $namespace::query()->each(function ($record) use ($namespace, $model, &$count, $defaultLocale) {
                // Ensure row_id exists
                if (empty($record->row_id)) {
                    $record->update(['row_id' => $record->id, 'iso' => $defaultLocale]);
                }

                // Get existing translations
                $existingTranslations = $namespace::query()
                    ->where('row_id', $record->row_id)
                    ->pluck('iso')
                    ->toArray();

                // Process each language that doesn't have a translation yet
                collect($this->languages)
                    ->reject(fn($language) => in_array($language, $existingTranslations, true))
                    ->each(function ($language) use ($namespace, $model, $record, &$count) {
                        $row = collect($record->getAttributes())->except('id')->toArray();
                        $row = array_merge($row, ['iso' => $language]);

                        // Use TranslationHelper to properly handle unique constraints
                        $row = TranslationHelper::handleUniqueConstraints($model, $row, $language);

                        try {
                            $namespace::create($row);
                            $count++;
                        } catch (\Throwable $e) {
                            $this->error("Could not generate translation for row {$record->id}: {$e->getMessage()}");
                        }
                    });
            });

            $this->info("{$count} translations have been generated for {$table} successfully.");
        });
    }

    /**
     * Check if the required MLang columns exist in the table
     *
     * @param string $table
     * @return bool
     */
    private function checkRequiredColumns(string $table): bool
    {
        try {
            return Schema::hasTable($table) &&
                Schema::hasColumn($table, 'row_id') &&
                Schema::hasColumn($table, 'iso');
        } catch (\Throwable $e) {
            $this->warn("Error checking columns for table {$table}: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Get models to process
     *
     * @param string|null $model
     */
    private function getModels(?string $model = null): void
    {
        $this->models = MLang::getModels();

        if ($model && Str::lower($model) !== 'all') {
            $modelsPath = Config::get('mlang.default_models_path', 'App\\Models\\');

            // Ensure proper namespacing
            if (!Str::startsWith($model, $modelsPath) && !Str::contains($model, '\\')) {
                $model = $modelsPath . Str::studly($model);
            } elseif (!Str::startsWith($model, $modelsPath) && Str::contains($model, '\\')) {
                $model = Str::studly($model);
            }

            if (!class_exists($model)) {
                $this->error("Model {$model} not found or does not exist.");
                exit(1);
            }

            $this->models = [$model];
        }
    }

    /**
     * Get languages to process
     *
     * @param string|null $locale
     */
    private function getLanguages(?string $locale = null): void
    {
        $this->languages = Config::get('mlang.languages', []);

        if (empty($this->languages)) {
            $this->error('No languages configured in mlang.languages config.');
            exit(1);
        }

        if ($locale) {
            $locale = Str::lower($locale);
            if (!in_array($locale, $this->languages, true)) {
                $this->error("Language {$locale} not found. Please add it to the config file.");
                exit(1);
            }

            $this->languages = [$locale];
        }
    }
}
