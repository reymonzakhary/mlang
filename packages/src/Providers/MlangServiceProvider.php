<?php

namespace Upon\Mlang\Providers;

use Illuminate\Support\ServiceProvider;
use Upon\Mlang\Contracts\MlangContractInterface;
use Upon\Mlang\Mlang;

class MlangServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(MlangContractInterface::class, function () {
           return new Mlang();
        });
        $this->app->alias(Mlang::class, 'm-lang');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(dirname(__DIR__,2) . '/database/migrations');
    }

}
