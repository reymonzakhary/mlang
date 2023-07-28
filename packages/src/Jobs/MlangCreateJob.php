<?php

namespace Upon\Mlang\Jobs;

use Doctrine\DBAL\Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MlangCreateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Model $model
    ){}

    /**
     * Execute the job.
     * @throws Exception
     */
    public function handle(): void
    {
        $row_id = $this->model->row_id;
        collect(Config::get('mlang.languages'))->reject(fn($language) =>
            $this->model->iso === $language
        )->each(function($language) use ($row_id) {
            $table = $this->model->getTable();
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $sm->listTableIndexes($table);
            $uniqueIndexesFound = collect(array_keys($indexesFound))->map(fn($c) =>
                Str::contains($c, 'unique') === false ?null: Str::replace(["{$table}_", '_unique'],['', ''],$c)
            )->filter()->toArray();
            $row = $this->model->toArray();
            $row = array_merge($row, ['iso' => $language, 'row_id' => $row_id]);
            unset($row['id']);
            collect($uniqueIndexesFound)->each(function($key) use (&$row){
                $row = array_merge($row, [$key => optional($row)[$key]. '_' . Str::random(3)]);
            });
            $this->model::create($row);
        });
    }
}
