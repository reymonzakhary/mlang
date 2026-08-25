<?php

namespace Upon\Mlang\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use Upon\Mlang\Providers\MlangServiceProvider;
use Upon\Mlang\Tests\Fixtures\Product;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [MlangServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('app.locale', 'en');
        $app['config']->set('mlang.languages', ['en', 'fr', 'de']);
        $app['config']->set('mlang.fallback_language', 'en');
        $app['config']->set('mlang.models', [Product::class]);
        $app['config']->set('mlang.observe_during_console', true);
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }
}
