<?php

declare(strict_types=1);

namespace kartik\file;

use core\web\AssetBundle;

/**
 * Theme Asset bundle for the FileInput Widget
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 * @since 1.0
 */
class FileInputThemeAsset extends BaseAsset
{
    /**
     * @inheritdoc
     */
    public array $depends = [
        FileInputAsset::class
    ];

    /**
     * Add file input theme file
     *
     * @param string $theme the theme file name
     * @return AssetBundle
     */
    public function addTheme($theme)
    {
        $file = SYS_DEBUG ? "theme.js" : "theme.min.js";
        if ($this->checkExists("themes/{$theme}/{$file}")) {
            $this->js[] = "themes/{$theme}/{$file}";
        }
        $file = SYS_DEBUG ? "theme.css" : "theme.min.css";
        if ($this->checkExists("themes/{$theme}/{$file}")) {
            $this->css[] = "themes/{$theme}/{$file}";
        }
        return $this;
    }

    /**
     * Check if file exists in path provided
     *
     * @param string $path the file path
     *
     * @return bool
     */
    protected function checkExists($path): bool
    {
        $path = fsPath(static::ROOT_DIR_PHP . $this->basePath . $path);
        return file_exists($path);
    }
}
