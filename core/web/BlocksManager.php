<?php

declare(strict_types=1);

namespace core\web;

use core\helpers\ArrayHelper;
use core\helpers\Html;
use core\base\Panel;
use core\data\LazyContainer;
use core\interfaces\ContainerInterface;
use core\exception\NotFoundException;

/**
 * Container of UI blocks
 */
class BlocksManager implements ContainerInterface
{
    /**
     * @var array Default block panel rendering options
     * @see Html::renderTagAttributes()
     * @see renderPanel()
     */
    public array $panelOptions = ['class' => 'site-panel'];

    /**
     * @var array Default panel content rendering options
     * @see Html::renderTagAttributes()
     * @see renderPanel()
     */
    public array $contentOptions = ['class' => 'site-panel-body'];

    /**
     * @var array Default panel header rendering options
     * @see Html::renderTagAttributes()
     * @see renderPanel()
     */
    public array $headingOptions = ['class' => 'site-panel-heading'];

    /**
     * @var array Blocks configuration.
     */
    protected array $blocks = [];

    /**
     * @var string Current controller's short class name (without namespace).
     */
    protected string $controller;

    /**
     * @var bool Explicitly show/hide left side.
     */
    protected bool $visibleSideLeft;

    /**
     * @var bool Explicitly show/hide right side.
     */
    protected bool $visibleSideRight;

    /**
     * @var bool Explicitly show/hide center top panels.
     */
    protected bool $visibleCenterTop;

    /**
     * @var bool Explicitly show/hide center bottom panels
     */
    protected bool $visibleCenterBottom;

    /**
     * @var bool Explicitly show/hide bottom footer panels
     */
    protected bool $visibleFooterBottom;

    /**
     * @var LazyContainer
     */
    protected LazyContainer $container;

    /**
     * Constructor.
     * 
     * @param string $controller Current controller class name.
     * @param array $options Options
     */
    public function __construct(string $controller, array $options = [])
    {
        $this->controller = get_class_short($controller);
        $blocks = ArrayHelper::remove($options, 'blocks', []);
        $this->blocks = ArrayHelper::merge($this->blocks, $blocks);
        $this->visibleSideLeft = ArrayHelper::remove($options, 'visibleSideLeft', true);
        $this->visibleSideRight = ArrayHelper::remove($options, 'visibleSideRight', true);
        $this->visibleCenterTop = ArrayHelper::remove($options, 'visibleCenterTop', true);
        $this->visibleCenterBottom = ArrayHelper::remove($options, 'visibleCenterBottom', true);
        $this->visibleFooterBottom = ArrayHelper::remove($options, 'visibleFooterBottom', true);
        $this->container = new LazyContainer;
    }

    /**
     * Show block
     * 
     * @param string $id Block id
     * 
     * @return self
     * @throws NotFoundException If block does not exists
     */
    public function show(string $id): self
    {
        if (!$this->has($id)) {
            throw new NotFoundException("Block '{$id}' does not exists");
        }

        $this->{'visible' . ucfirst($id)} = true;
        return $this;
    }

    /**
     * Hide block
     * 
     * @param string $id Block id
     * 
     * @return self
     * @throws NotFoundException If block does not exists
     */
    public function hide(string $id): self
    {
        if (!$this->has($id)) {
            throw new NotFoundException("Block '{$id}' does not exists");
        }

        $this->{'visible' . ucfirst($id)} = false;
        return $this;
    }

    /**
     * Clear rendered blocks cache.
     * 
     * @return self
     */
    public function clearCache(): self
    {
        $this->container->clear();
        return $this;
    }

    /**
     * Get left side block.
     * 
     * @return string
     */
    public function sideLeft(): string
    {
        return $this->get(__FUNCTION__);
    }

    /**
     * Get right side block.
     * 
     * @return string
     */
    public function sideRight(): string
    {
        return $this->get(__FUNCTION__);
    }

    /**
     * Get center top block.
     * 
     * @return string
     */
    public function centerTop(): string
    {
        return $this->get(__FUNCTION__);
    }

    /**
     * Get center bottom block.
     * 
     * @return string
     */
    public function centerBottom(): string
    {
        return $this->get(__FUNCTION__);
    }

    /**
     * Get footer bottom block.
     * 
     * @return string
     */
    public function footerBottom(): string
    {
        return $this->get(__FUNCTION__);
    }

    /**
     * {@inheritDoc}
     * 
     * Get block.
     * 
     * @param string $id Block id
     * 
     * @return string
     * @throws NotFoundException If block does not exists
     */
    public function get(string $id): string
    {
        if (!$this->has($id)) {
            throw new NotFoundException("Block '{$id}' does not exists");
        }

        if (!$this->{'visible' . ucfirst($id)}) {
            return '';
        }

        return $this->container->lazy($id, fn () => $this->renderBlock($id));
    }

    /**
     * {@inheritDoc}
     * 
     * Whether block with given id is exists.
     * 
     * @param string $id Block id
     * 
     * @return bool
     */
    public function has(string $id): bool
    {
        return property_exists($this, 'visible' . ucfirst($id));
    }

    /**
     * Render block.
     * 
     * @param string $id Block id (key of `self::$blocks` attribute).
     * 
     * @return string
     */
    protected function renderBlock(string $id): string
    {
        $result = [];
        $blocks = $this->blocks[$id] ?? [];
        foreach ($blocks as $class => $options) {
            /** @var Panel $panel */
            $panel = new $class($options);
            if ($this->isPanelActive($panel)) {
                $result[] = $this->renderPanel($panel);
            }
        }

        return implode("\n", $result);
    }

    /**
     * Whether panel is active with current controller.
     * 
     * @param Panel $panel
     * 
     * @return bool True if active
     */
    protected function isPanelActive(Panel $panel): bool
    {
        return $panel->on($this->controller) && !$panel->off($this->controller);
    }

    /**
     * Render panel.
     * 
     * @param Panel $panel Panel object
     * 
     * @return string
     */
    protected function renderPanel(Panel $panel): string
    {
        $contentOptions = $this->contentOptions;
        $contentOptions = array_merge($this->contentOptions, $panel->contentOptions);
        $content = Html::tag('div', $panel->render(), $contentOptions) . "\n";

        $title = !empty($panel->title) ? Html::tag('h4', $panel->title) . "\n" : '';
        if ($title) {
            $headingOptions = $this->contentOptions;
            $headingOptions = array_merge($this->headingOptions, $panel->headingOptions);
            $title = Html::tag('div', $title, $headingOptions) . "\n";
        }

        $panelOptions = $this->panelOptions;
        $panelOptions = array_merge($this->panelOptions, $panel->panelOptions);
        Html::addCssClass($panelOptions, $panel->class);
        $html = Html::tag('div', $title . $content, $panelOptions) . "\n";

        return $html;
    }
}