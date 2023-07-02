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
        $this->configure();
        $this->offerPublishing();
        $this->registerMlang();
        $this->registerCommands();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(dirname(__DIR__,2) . '/database/migrations');
    }

    /**
     * Setup the configuration for Mlang.
     *
     * @return void
     */
    protected function configure()
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/mlang.php', 'mlang');
    }

    /**
     * Setup the resource publishing group for Mlang.
     *
     * @return void
     */
    protected function offerPublishing()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/mlang.php' => config_path('mlang.php'),
            ], 'mlang');
        }
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
                    \Upon\Mlang\Console\MLangMigrateCommand::class,
            ]);
        }
    }

    /**
     * Register the application bindings.
     *
     * @return void
     */
    protected function registerMlang()
    {
        $this->app->bind(MlangContractInterface::class, function ($app) {
            return new Mlang($app);
        });
        $this->app->alias('mlang', 'Upon\Mlang');
    }

}
