<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{

    public function index()
    {
        app()->setLocale('ar');
//        return Category::trfind(1);
        $category = Category::create([
            'name' => 'reymodeeeeeenwe',
            'slug' => 'reyeedmeeeoenwe'
        ]);
//
        return $category;
//        app()->setLocale('en');
        dd(Category::query()->find( 88, 'nl'));
//        $category = Category::whereSlug('aut-quasi-nostrum-sed-asperiores-ut-ratione-temporibus')->first()
//            ->products()->create(['iso' => 'ar', 'name' => 'producwtss3' , 'slug' => 'prod3uctwss']);
//        dd($category);
    }

    public function store(
        Request $request
    )
    {
        $category = Category::createToLanguage([
            [
                'name' => 'exc',
                'iso' => 'en'
            ],[
                'name' => 'exc',
                'iso' => 'nl'
            ]
        ]);

        Category::where(['iso' => 'en', 'id' => 1])->first();
    }
}
