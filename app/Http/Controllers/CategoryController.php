<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{

    public function index()
    {
        $category = Category::whereSlug('aut-quasi-nostrum-sed-asperiores-ut-ratione-temporibus')->first()
            ->products()->create(['iso' => 'ar', 'name' => 'producwtss3' , 'slug' => 'prod3uctwss']);
        dd($category);
    }
}
