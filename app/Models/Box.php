<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Upon\Mlang\Models\MlangModel;

class Box extends MlangModel
{
    use HasFactory;

    protected $fillable = [
        'name'
    ];
}
