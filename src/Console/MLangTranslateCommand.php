<?php

namespace Upon\Mlang\Console;

use Illuminate\Console\Command;
use Upon\Mlang\Contracts\TranslatorInterface;
use Upon\Mlang\Helpers\AutoTranslateHelper;
use Upon\Mlang\Helpers\LanguageHelper;

/**
 * Fill missing locale rows using the configured translator driver.
 *
 *   php artisan mlang:translate                 # all models, all missing locales
 *   php artisan mlang:translate Product --to=fr,de --from=en
 */
class MLangTranslateCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'mlang:translate
                            {model? : Model class or short name (default: all configured models)}
                            {--to= : Comma-separated target locales (default: all configured)}
                            {--from= : Source locale (default: fallback_language)}';

    /**
     * @var string
     */
    protected $description = 'Create missing translation rows using the configured translator driver';

    /**
     * Execute the console command.
     *
     * @param TranslatorInterface $translator
     * @return int
     */
    public function handle(TranslatorInterface $translator): int
    {
        $models = $this->resolveModels($this->argument('model'));

        if (empty($models)) {
            $this->error('No matching translatable models found.');
            return self::FAILURE;
        }

        $targets = $this->option('to') ? array_map('trim', explode(',', $this->option('to'))) : null;
        $from = $this->option('from') ?: null;
        $total = 0;

        $this->info('Translator: ' . $translator::class);

        foreach ($models as $class) {
            $created = AutoTranslateHelper::fillMissing(new $class, $translator, $targets, $from);
            $this->line("  {$class}: {$created} rows created");
            $total += $created;
        }

        $this->info("{$total} translation rows created.");

        return self::SUCCESS;
    }

    /**
     * Resolve the model argument (FQCN, or short name matched against config) to class names.
     *
     * @param string|null $model
     * @return string[]
     */
    protected function resolveModels(?string $model): array
    {
        $configured = LanguageHelper::getConfiguredModels();

        if ($model === null) {
            return array_values(array_filter($configured, 'class_exists'));
        }

        if (class_exists($model)) {
            return [$model];
        }

        return array_values(array_filter(
            $configured,
            fn ($class) => class_exists($class) && strcasecmp(class_basename($class), $model) === 0
        ));
    }
}
