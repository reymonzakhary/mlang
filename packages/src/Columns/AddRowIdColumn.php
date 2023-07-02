<?php

namespace Upon\Mlang\Columns;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AddRowIdColumn extends Migration
{
    /**
     * Run the migrations.
     */
    public static function up(
        $table
    ): void
    {
        $sm = Schema::getConnection()->getDoctrineSchemaManager();
        $indexesFound = $sm->listTableIndexes($table);
        $uniqueIndexesFound = collect(array_keys($indexesFound))->filter(fn($c) => Str::contains($c, 'unique'))->all();
        if(Schema::hasTable($table)) {
            if(!Schema::hasColumn($table, 'row_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->unsignedBigInteger('row_id')->after('id')->index()->nullable();
                });
            }

            if (!Schema::hasColumn($table, 'base_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->unsignedBigInteger('base_id')->index()->nullable();
                });
            }

            if (!Schema::hasColumn($table, 'iso')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->string('iso')->nullable();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
