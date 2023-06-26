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
        $this->app->bind(MlangContractInterface::class, function ($app) {
           return new Mlang($app);
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

    /**
     * Register the MLang commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
            if ($this->app->runningInConsole()) {
                $this->commands([
                    Upon\Mlang\Console\MLangMigrateCommand::class,
            ]);
        }
    }

}
