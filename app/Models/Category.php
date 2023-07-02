<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Upon\Mlang\Models\MlangModel;

class Category extends MlangModel
{
    use HasFactory;

    protected $fillable = [
        'name','slug'
    ];

    /**
     * @return HasMany
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }
}
