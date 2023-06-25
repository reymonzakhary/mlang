<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Upon\Mlang\Contracts\MlangContractInterface;
use Upon\Mlang\Models\Traits\MlangTrait;

class Category extends Model implements MlangContractInterface
{
    use HasFactory, MlangTrait;

    protected $fillable = [
        'name','slug'
    ];
}
