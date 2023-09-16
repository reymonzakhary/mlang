<?php

namespace Upon\Mlang\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Upon\Mlang\Models\MlangModel;

class MLangGenerateCommand extends Command
{
    /**
     * List of models
     * @var array
     */
    protected array $models = [];

    /**
     * List of models
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
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->getModels($this->argument('model'));
        $this->getLanguages($this->argument('locale'));
        $this->createMultiLanguageRecords();
    }

    /**
     *
     */
    private function createMultiLanguageRecords():void
    {
        collect($this->models)->map(function($namespace){
            $table = (new $namespace)->getTable();
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $sm->listTableIndexes($table);
            $uniqueIndexesFound = collect(array_keys($indexesFound))->map(fn($c) =>
            Str::contains($c, 'unique') === false ?null: Str::replace(["{$table}_", '_unique'],['', ''],$c)
            )->filter()->toArray();
            $count = 0;
            $namespace::get()->map(function ($record) use ($namespace, $uniqueIndexesFound,&$count){
                $record->row_id?? $record->update(['row_id'=>$record->id, 'iso' => Config::get('app.locale')]);
                collect($this->languages)
                    ->reject(fn($language) =>
                    in_array($language, $record->where('row_id', $record->row_id)->pluck('iso')->toArray(), true)
                    )
                    ->each(function ($language) use ($namespace,$record, $uniqueIndexesFound, &$count){
                        $row = collect($record)->except('id')->toArray();
                        $row = array_merge($row, ['iso' => $language]);
                        collect($uniqueIndexesFound)->each(function($key) use (&$row){
                            $row = array_merge($row, [$key => optional($row)[$key]. '_' . Str::random(3)]);
                        });
                        try {
                            $namespace::create($row);
                            $count++;
                        } catch (\Exception $e) {
                            $this->info(__("We could not generate translations for row {$record?->id}, exception: {$e->getMessage()}"));
                        }
                    });
            });
            $this->info(__("{$count} translations has been generated for {$table} successfully."));
        });
    }

    /**
     * @param string|null $model
     */
    private function getModels(
        string $model = null
    ): void
    {
        $this->models = (new MlangModel())->getModels();

        if($model && Str::lower($model) !== 'all') {
            $model = Config::get('mlang.default_models_path').
                collect(explode('\\', $model))->map(fn($model) => Str::ucfirst($model))->implode('\\', $model);
            if(!class_exists($model)) {
                $this->info(__("Model {$model} not found, or not exists."));
                exit;
            }
            $this->models = [
                $model
            ];
        }
    }

    /**
     * @param string|null $locale
     */
    private function getLanguages(
        string $locale = null
    ): void
    {
        $this->languages = Config::get('mlang.languages');
        if($locale) {
            if(!in_array($locale, $this->languages, true)) {
                $this->info(__("Language {$locale} not found, or not exists please add it to the config file."));
                exit;
            }
            $this->languages = [
                Str::lower($locale)
            ];
        }
    }
}
