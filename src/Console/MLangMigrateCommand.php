<?php

namespace Upon\Mlang\Console;

use Illuminate\Console\Command;
use Upon\Mlang\Columns\AddRowIdColumn;
use Upon\Mlang\Models\MlangModel;

class MLangMigrateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mlang:migrate';

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
        $this->addCollumToModels();
    }

    private function addCollumToModels():void
    {
        $models = (new MlangModel())->getModels();
        $tables =  (new MlangModel())->getTableNames();


        if(empty($tables)) {
            $this->info("No tables were found.");
        }

        foreach ($tables as $k => $table) {
            if($table) {
                AddRowIdColumn::up($table, "\\".$models[$k]);
                $this->info("Column has been added to {$table} table.");
            }
        }
    }
}
