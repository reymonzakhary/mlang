<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Upon\Mlang\Models\MlangModel;
use Upon\Mlang\Models\Traits\MlangTrait;

class Category extends MlangModel
{
    use HasFactory;

    protected $fillable = [
        'name','slug'
    ];
}
