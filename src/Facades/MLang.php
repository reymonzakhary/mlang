<?php

namespace Upon\Mlang\Facades;

use Illuminate\Support\Facades\Facade;
use Illuminate\Database\Eloquent\Model;

/**
 * MLang Facade
 *
 * Provides a fluent interface for multi-language operations
 *
 * @method static \Upon\Mlang\MLang setCurrentModel(object|string $model)
 * @method static string|null getCurrentModel()
 * @method static object|string|null getModelInstance()
 * @method static string getModelName()
 * @method static array getTableNames()
 * @method static array getModels()
 * @method static string|null getTableName()
 * @method static \Upon\Mlang\MLang migrate(?string $table = null)
 * @method static \Upon\Mlang\MLang rollback(?string $table = null)
 * @method static \Upon\Mlang\MLang generate(?string $model = null, ?string $locale = null)
 * @method static array createMultiLanguage(array $attributes, ?array $languages = null, ?array $translatedAttributes = null)
 * @method static array getStats()
 * @method static \Illuminate\Support\Collection getAllTranslations(int|string $id)
 * @method static int updateAllTranslations(int|string $id, array $attributes)
 * @method static int deleteAllTranslations(int|string $id)
 * @method static Model|null copyToLanguage(Model|int|string $sourceModelOrId, string $targetLanguage, array $overrideAttributes = [])
 * @method static \Illuminate\Support\Collection getIncompleteTranslations()
 * @method static float getCoverage()
 *
 * @see \Upon\Mlang\MLang
 */
class MLang extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'mlang';
    }

    /**
     * Set the model to work with
     *
     * @param object|string $model Either a model class name or instance
     * @return \Upon\Mlang\MLang
     */
    public static function forModel(object|string $model): \Upon\Mlang\MLang
    {
        $instance = static::getFacadeRoot();
        $instance->setCurrentModel($model);

        return $instance;
    }
}
