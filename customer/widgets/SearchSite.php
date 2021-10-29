<?php

declare(strict_types=1);

namespace customer\widgets;

use Sys;
use common\widgets\SearchSite as MainSearchSite;
use core\helpers\Url;

/**
 * Widget for search on site.
 */
class SearchSite extends \core\widgets\Widget
{
    /**
     * @var string
     */
    public string $suggesterController;

    /**
     * @var int Minimal symbols to enter to suggestions appears.
     */
    public int $minLength;

    /**
     * @var string
     */
    protected string $controller;

    /**
     * @var string
     */
    protected string $action;

    public function run()
    {
        $action = $this->determineSuggesterMethod();
        if (!$action) {
            return;
        }

        $route = strtolower($this->determineController() . '/' . $this->determineAction());
        echo MainSearchSite::widget([
            'suggestUrl' => 'suggest/' . strtolower($action),
            'action' => Url::to([$route]),
            'placeholder' => 'Поиск в разделе...',
            'minLength' => $this->minLength
        ]);
    }

    protected function determineSuggesterMethod(): string
    {
        $suggestMethod = $this->determineController() . $this->determineAction();
        return method_exists($this->suggesterController, 'action' . ucfirst($suggestMethod)) ? $suggestMethod : '';
    }

    protected function determineController(): string
    {
        if (!isset($this->controller)) {
            /** @var \core\base\Controller */
            $controller = Sys::$app->getController();
            $class = get_class_short($controller);
            $this->controller = str_replace('Controller', '', $class);
        }

        return $this->controller;
    }

    protected function determineAction(): string
    {
        if (!isset($this->action)) {
            /** @var \core\middleware\ControllerRunner */
            $component = Sys::$app->getKernelById('ControllerRunner');
            $action = $component->getAction();
            $routingAction = str_replace('action', '', $action);
            $this->action = $routingAction === 'Index' ? '' : $routingAction;
        }

        return $this->action;
    }
}
