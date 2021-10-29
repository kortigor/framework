<?php

declare(strict_types=1);

namespace core\base;

use Closure;

/**
 * Abstract class to implement UI panels.
 */
abstract class Panel
{
    /**
     * @var string Panel title;
     */
    public string $title;

    /**
     * @var string CSS class(es) to apply to panel's block.
     */
    public string $class = '';

    /**
     * @var array Default panel rendering options
     * @see Html::renderTagAttributes()
     * @see \core\web\BlocksManager::renderPanel()
     */
    public array $panelOptions = ['class' => 'site-panel'];

    /**
     * @var array Default panel content rendering options
     * @see Html::renderTagAttributes()
     * @see \core\web\BlocksManager::renderPanel()
     */
    public array $contentOptions = ['class' => 'site-panel-body'];

    /**
     * @var array Default panel header rendering options
     * @see Html::renderTagAttributes()
     * @see \core\web\BlocksManager::renderPanel()
     */
    public array $headingOptions = ['class' => 'site-panel-heading'];

    /**
     * @var array List of controllers where panel to show.
     * If value is `['*']` panel shown for all controllers (Default).
     */
    protected array $on = ['*'];

    /**
     * @var array List of controllers where panel to hide
     * If value is `['*']` panel hidden for all controllers.
     */
    protected array $off = [];

    /**
     * Constructor.
     * 
     * @param array $options Panel options
     */
    public function __construct(array $options = [])
    {
        foreach ($options as $option => $value) {
            $this->$option = $value instanceof Closure ? $value() : $value;
        }
    }

    /**
     * Render panel content.
     * 
     * @return string
     */
    abstract function render(): string;

    /**
     * Check panel switched ON.
     * 
     * @param string $controller
     * 
     * @return bool True if panel ON
     */
    public function on(string $controller): bool
    {
        return $this->checkSetting($controller, 'on');
    }

    /**
     * Check panel switched OFF.
     * 
     * @param string $controller
     * 
     * @return bool True if panel OFF
     */
    public function off(string $controller): bool
    {
        return $this->checkSetting($controller, 'off');
    }

    /**
     * Perform setting checkSetting.
     * 
     * @param string $controller
     * @param string $setting
     * 
     * @return bool
     */
    protected function checkSetting(string $controller, string $setting): bool
    {
        if ($this->$setting === ['*']) {
            return true;
        }

        foreach ($this->$setting as $opt) {
            if (($opt . 'Controller') === $controller) {
                return true;
            }
        }

        return false;
    }
}