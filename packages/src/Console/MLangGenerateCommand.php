<?php

namespace Upon\Mlang\Console;

use Illuminate\Console\Command;
use Upon\Mlang\Columns\AddRowIdColumn;
use Upon\Mlang\Models\MlangModel;

class MLangGenerateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mlang:generate';

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
        $this->createMultiLanguageRecords();
    }

    private function createMultiLanguageRecords():void
    {
        $namespaces =  (new MlangModel())->getModels();

        if(empty($namespaces)) {
            $this->info("No tables were found.");
        }
        $languages = config('mlang.languages');

        collect($namespaces)->map(function($namespace) use($languages){

            $model = $namespace::get();

            collect($model)->map(function ($record) use ($namespace,$languages){

                if (($key = array_search($record->iso, $languages)) !== false) {
                    unset($languages[$key]);
                }
                collect($languages)->map(function ($language) use ($namespace,$record){
                    $record->iso = $language;
                    $newRecord = collect($record)->except('id','row_id')->toArray();
                    $namespace::create($newRecord);
                });

            });
        });
    }
}
