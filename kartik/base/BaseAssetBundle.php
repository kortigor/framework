<?php

declare(strict_types=1);

namespace kartik\base;

use core\web\AssetBundle;

/**
 * Base asset bundle used for all Krajee extensions.
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 */
class BaseAssetBundle extends AssetBundle
{
    /**
     * Real filesystem base directory of all Krajee assets.
     * Used to check the existence of files.
     * @author Kort.
     */
    const ROOT_DIR_PHP = '/frontend/web/';
    /**
     * Unique value to set an empty asset via AssetManager configuration.
     */
    const EMPTY_ASSET = ['N0/@$$3T$'];
    /**
     * Unique value to set an empty asset file path via AssetManager configuration.
     */
    const EMPTY_PATH = ['N0/P@T#'];
    /**
     * Unique value identifying a Krajee asset
     */
    const KRAJEE_ASSET = ['K3/@$$3T$'];
    /**
     * @inheritdoc
     */
    public array $js = self::KRAJEE_ASSET;
    /**
     * @inheritdoc
     */
    public array $css = self::KRAJEE_ASSET;

    public function __construct()
    {
        $this->init();
    }

    public function init()
    {
        if ($this->js === self::KRAJEE_ASSET) {
            $this->js = [];
        }
        if ($this->css === self::KRAJEE_ASSET) {
            $this->css = [];
        }
    }

    /**
     * Adds a language JS locale file
     *
     * @param string $lang the ISO language code
     * @param string $prefix the language locale file name prefix
     * @param string $dir the language file directory relative to source path
     * @param bool $min whether to auto use minified version
     *
     * @return AssetBundle instance
     */
    public function addLanguage(string $lang = '', string $prefix = '', string $dir = '', bool $min = false): AssetBundle
    {
        if (empty($lang) || substr($lang, 0, 2) === 'en') {
            return $this;
        }

        $ext = $min ? (SYS_DEBUG ? ".min.js" : ".js") : ".js";
        $file = "{$prefix}{$lang}{$ext}";
        if (empty($dir)) {
            $dir = 'js';
        } elseif ($dir === '/') {
            $dir = '';
        }

        $path = fsPath(static::ROOT_DIR_PHP . $this->basePath . $dir);
        if (!Config::fileExists("{$path}/{$file}")) {
            $lang = Config::getLang($lang);
            $file = "{$prefix}{$lang}{$ext}";
        }

        if (Config::fileExists("{$path}/{$file}")) {
            $this->js[] = empty($dir) ? $file : "{$dir}/{$file}";
        }

        return $this;
    }


    /**
     * Set up CSS and JS asset arrays based on the base-file names
     *
     * @param string $type whether 'css' or 'js'
     * @param array $files the list of 'css' or 'js' basefile names
     */
    protected function setupAssets(string $type, array $files = []): void
    {
        if ($this->$type === self::KRAJEE_ASSET) {
            $srcFiles = [];
            $minFiles = [];
            foreach ($files as $file) {
                $srcFiles[] = "{$file}.{$type}";
                $minFiles[] = "{$file}.min.{$type}";
            }
            $this->$type = SYS_DEBUG ? $srcFiles : $minFiles;
        } elseif ($this->$type === self::EMPTY_ASSET) {
            $this->$type = [];
        }
    }
}