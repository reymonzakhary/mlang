<?php

namespace Upon\Mlang\Providers;

use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
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
        $this->runCommands();
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
    protected function configure(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/mlang.php', 'mlang');
    }

    /**
     * Setup the resource publishing group for Mlang.
     *
     * @return void
     */
    protected function offerPublishing(): void
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
    protected function registerCommands(): void
    {
            if ($this->app->runningInConsole()) {
                $this->commands([
                    \Upon\Mlang\Console\MLangMigrateCommand::class,
                    \Upon\Mlang\Console\MLangGenerateCommand::class,
            ]);
        }
    }

    /**
     * Register the application bindings.
     *
     * @return void
     */
    protected function registerMlang(): void
    {
        $this->app->bind(MlangContractInterface::class, function ($app) {
            return new Mlang($app);
        });
        $this->app->alias('mlang', 'Upon\Mlang');
    }

    /**
     *  Run with the default migrations command.
     *  @return void
     */
    protected function runCommands(): void
    {
        if(app()->runningInConsole()) {
            // we are running in the console
            $argv = \Request::server('argv', null);
            if($argv[0] === 'artisan' && \Illuminate\Support\Str::contains($argv[1],'migrate')) {
                Event::listen(function (MigrationsEnded $event) use ($argv) {
                    Artisan::call('mlang:migrate');
                });
            }
        }
    }

}
