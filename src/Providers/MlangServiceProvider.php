<?php

namespace Upon\Mlang\Providers;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Console\Output\ConsoleOutput;
use Upon\Mlang\MLang;

class MlangServiceProvider extends ServiceProvider
{
    /**
     * Console output instance
     *
     * @var ConsoleOutput
     */
    protected $output;

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->configure();
        $this->offerPublishing();
        $this->registerMlang();
        $this->registerCommands();

        // Initialize console output
        $this->output = new ConsoleOutput();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(dirname(__DIR__, 2) . '/database/migrations');
        $this->registerMigrationHooks();
        $this->registerSeedingHooks();
        $this->registerRollbackHooks();
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
        // Always register commands so they can be called via Artisan::call()
        // from both console and HTTP contexts
        $this->commands([
            \Upon\Mlang\Console\MLangMigrateCommand::class,
            \Upon\Mlang\Console\MLangGenerateCommand::class,
        ]);
    }

    /**
     * Register the application bindings.
     *
     * @return void
     */
    protected function registerMlang(): void
    {
        $this->app->bind('mlang', function ($app) {
            return new MLang($app);
        });
        // Register the facade
        $this->registerFacade();
//        $this->app->alias('Mlang', '\Upon\Mlang\Facades\MlangFacade::class');
    }

    /**
     * Register hooks to run MLang migrate after main migrations.
     *
     * @return void
     */
    protected function registerMigrationHooks(): void
    {
        // Skip if auto migrate is disabled
        if (!config('mlang.auto_migrate', false)) {
            return;
        }

        // Laravel 8+ approach
        if (class_exists(MigrationsEnded::class)) {
            Event::listen(MigrationsEnded::class, function () {
                $this->runMLangMigrate();
            });
        }

        // For all Laravel versions
        Event::listen(CommandFinished::class, function (CommandFinished $event) {
            // Check if it's a migration command
            // (migrate, migrate:fresh, migrate:refresh, etc.) but not rollback
            if (str_starts_with($event->command, 'migrate') &&
                !str_contains($event->command, 'rollback') &&
                !$event->input->hasOption('no-mlang')) {

                $this->runMLangMigrate();
            }
        });
    }

    /**
     * Register hooks to run MLang generate after seeding.
     *
     * @return void
     */
    protected function registerSeedingHooks(): void
    {
        // Skip if auto generate is disabled
        if (!config('mlang.auto_generate_after_seed', false)) {
            return;
        }

        // For all Laravel versions
        Event::listen(CommandFinished::class, function (CommandFinished $event) {
            // Check if it's a seeding command
            if (($event->command === 'db:seed' ||
                    ($event->command === 'migrate' && $event->input->hasOption('seed') && $event->input->getOption('seed'))) &&
                !$event->input->hasOption('no-mlang')) {

                $this->runMLangGenerate();
            }
        });
    }

    /**
     * Register hooks to run MLang rollback after main migrations rollback.
     *
     * @return void
     */
    protected function registerRollbackHooks(): void
    {
        // Skip if auto rollback is disabled
        if (!config('mlang.auto_rollback', false)) {
            return;
        }

        // For all Laravel versions
        Event::listen(CommandFinished::class, function (CommandFinished $event) {
            // Check if it's a rollback command
            if ((str_starts_with($event->command, 'migrate:rollback') ||
                    str_starts_with($event->command, 'migrate:reset') ||
                    str_starts_with($event->command, 'migrate:fresh')) &&
                !$event->input->hasOption('no-mlang')) {

                $this->runMLangRollback();
            }
        });
    }

    /**
     * Run the MLang migrate command.
     *
     * @return void
     * @throws BindingResolutionException
     */
    protected function runMLangMigrate(): void
    {
        try {
            // Write to terminal
            $this->output->writeln('<info>Running MLang migrate after migrations...</info>');

            // Execute command with output displayed in terminal
            Artisan::call('mlang:migrate', [], $this->output);

            // Log the output for debugging
            if (config('mlang.debug_output', false)) {
                $this->app->make('log')->info('MLang migration completed');
            }
        } catch (\Throwable $e) {
            $this->output->writeln('<error>Error running MLang migrate: ' . $e->getMessage() . '</error>');
            $this->app->make('log')->error('Error running MLang migrate: ' . $e->getMessage());
        }
    }

    /**
     * Run the MLang generate command with config settings.
     *
     * @return void
     * @throws BindingResolutionException
     */
    protected function runMLangGenerate(): void
    {
        try {
            // Only run generate if the required columns exist
            if (!$this->checkTablesHaveRequiredColumns()) {
                $this->output->writeln('<comment>Skipping MLang generate after seeding - required columns not found in tables</comment>');
                return;
            }

            $model = config('mlang.auto_generate_models', 'all');
            $locale = config('mlang.auto_generate_locale', null);

            $this->output->writeln('<info>Running MLang generate after seeding...</info>');

            $params = array_filter([
                'model' => $model !== 'all' ? $model : null,
                'locale' => $locale,
            ]);

            // Execute command with output displayed in terminal
            Artisan::call('mlang:generate', $params, $this->output);

            // Log the completion for debugging
            if (config('mlang.debug_output', false)) {
                $this->app->make('log')->info('MLang generate completed');
            }
        } catch (\Throwable $e) {
            $this->output->writeln('<error>Error running MLang generate: ' . $e->getMessage() . '</error>');
            $this->app->make('log')->error('Error running MLang generate: ' . $e->getMessage());
        }
    }

    /**
     * Run the MLang migrate command with rollback option.
     *
     * @return void
     * @throws BindingResolutionException
     */
    protected function runMLangRollback(): void
    {
        try {
            $this->output->writeln('<info>Running MLang rollback after migrations rollback...</info>');

            // Execute command with output displayed in terminal
            Artisan::call('mlang:migrate', ['--rollback' => true], $this->output);

            // Log the completion for debugging
            if (config('mlang.debug_output', false)) {
                $this->app->make('log')->info('MLang rollback completed');
            }
        } catch (\Throwable $e) {
            $this->output->writeln('<error>Error running MLang rollback: ' . $e->getMessage() . '</error>');
            $this->app->make('log')->error('Error running MLang rollback: ' . $e->getMessage());
        }
    }

    /**
     * Check if at least one table has the required MLang columns
     *
     * @return bool
     * @throws BindingResolutionException
     */
    protected function checkTablesHaveRequiredColumns(): bool
    {
        try {
            if(
                !config('mlang.auto_generate_after_seed', false)
            ) {
                return false;
            }

            $tables = \Upon\Mlang\Facades\MLang::getTableNames();

            if (empty($tables)) {
                $this->output->writeln('<comment>No tables defined in MLang config</comment>');
                return false;
            }

            foreach ($tables as $table) {
                if (Schema::hasTable($table) &&
                    Schema::hasColumn($table, 'row_id') &&
                    Schema::hasColumn($table, 'iso')) {
                    return true;
                }
            }

            $this->output->writeln('<comment>No tables found with required MLang columns (row_id, iso)</comment>');
            return false;
        } catch (\Throwable $e) {
            $this->output->writeln('<error>Error checking MLang columns: ' . $e->getMessage() . '</error>');
            $this->app->make('log')->error('Error checking MLang columns: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Register the facade without modifying the application configuration.
     *
     * @return void
     */
    protected function registerFacade(): void
    {
        // Get the alias loader instance
        $loader = AliasLoader::getInstance();

        // Register the facade
        $loader->alias('MLang', \Upon\Mlang\Facades\MLang::class);
    }
}
