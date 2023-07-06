<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Upon\Mlang\Models\MlangModel;

class Product extends MlangModel
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug','category_id'
    ];

    /**
     * @return BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
