<?php

declare(strict_types=1);

namespace customer;

use Sys;
use core\helpers\ArrayHelper;
use core\routing\Rule;

/**
 * Reload Url generator from any not frontend application.
 * There is possibility to generate frontend urls from backend.
 * After reload is possible to use standard Url::to() method to generate frontend urls.
 */
class FrontendUrlGenerator
{
    /**
     * @var string Path to config file contains frontend url generator rules.
     */
    public static string $rulesConfigFilePath = '/frontend/config/main.php';

    /**
     * @var string Path to url generator rules in configuration array.
     */
    public static string $rulesConfigPath = 'container.singletons.' . \core\routing\Router::class . '.@p.rules';

    /**
     * @var bool
     */
    protected static bool $isLoaded = false;

    /**
     * Load frontend url generator rules.
     * 
     * @return void
     */
    public static function load(): void
    {
        if (static::$isLoaded) {
            return;
        }

        $config = require fsPath(static::$rulesConfigFilePath);
        $rules = ArrayHelper::getValue($config, static::$rulesConfigPath, []);

        foreach ($rules as $record) {
            $rule = new Rule(...$record);
            Sys::$app->getRouter()->getGenerator()->addRule($rule);
        }

        static::$isLoaded = true;
    }

    private function __construct()
    {
        // Can not instantiate
    }
}
