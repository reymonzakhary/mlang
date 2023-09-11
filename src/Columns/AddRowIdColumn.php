<?php

namespace Upon\Mlang\Columns;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

class AddRowIdColumn extends Migration
{
    /**
     * Run the migrations.
     */
    public static function up(
        $table,
        $model
    ): void
    {
        if(Schema::hasTable($table)) {
            if(!Schema::hasColumn($table, 'row_id')) {
                Schema::table($table, function (Blueprint $table) use ($model) {
                    $table->foreignIdFor($model, 'row_id')->index()->nullable();
                });
            }

            if (!Schema::hasColumn($table, 'iso')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->string('iso')->nullable();
                });
            }
        }
    }
}
