<?php

declare(strict_types=1);

namespace core\web;

use Throwable;
use core\exception\InvalidConfigException;
use core\interfaces\ViewHandlerInterface;
use core\widgets\Block;
use core\helpers\ArrayHelper;
use core\helpers\Json;
use core\helpers\Html;

class View
{
    /**
     * @var int The location of registered JavaScript code block or files.
     * This means the location is in the `<head>` tag section.
     */
    const POS_HEAD = 1;

    /**
     * @var int The location of registered JavaScript code block or files.
     * This means the location is at the beginning of the `<body>` tagg section.
     */
    const POS_BODY_BEGIN = 2;

    /**
     * @var int The location of registered JavaScript code block or files.
     * This means the location is at the end of the `<body>` tag section.
     */
    const POS_BODY_END = 3;

    /**
     * @var int The location of registered JavaScript code block.
     * This means the JavaScript code block will be enclosed within `jQuery(document).ready()`.
     */
    const POS_READY = 4;

    /**
     * @var int The location of registered JavaScript code block.
     * This means the JavaScript code block will be enclosed within `jQuery(window).load()`.
     */
    const POS_LOAD = 5;

    /**
     * @var string This is internally used as the placeholder for receiving the content registered for the head section.
     */
    const PH_HEAD = '<![CDATA[SITE-BLOCK-HEAD]]>';

    /**
     * @var string This is internally used as the placeholder for receiving the content registered for the beginning of the body section.
     */
    const PH_BODY_BEGIN = '<![CDATA[SITE-BLOCK-BODY-BEGIN]]>';

    /**
     * @var string This is internally used as the placeholder for receiving the content registered for the end of the body section.
     */
    const PH_BODY_END = '<![CDATA[SITE-BLOCK-BODY-END]]>';

    /**
     * @var array Associative array of default object properties values in pairs 'prop' => $value
     */
    public static array $defaults = [];

    /**
     * @var array a list of named output blocks. The keys are the block names and the values
     * are the corresponding block content. You can call `beginBlock()` and `endBlock()`
     * to capture small fragments of a view. They can be later accessed somewhere else
     * through this property.
     */
    public array $blocks = [];

    /**
     * @var string Page title
     */
    public string $title;

    /**
     * Associative array of any options.
     * 
     * @var array
     */
    public array $options = [];

    /**
     * @var array Registered metatags.
     * @see registerMetaTag()
     */
    public array $metaTags = [];

    /**
     * @var array Registered `<link>` tags.
     * @see registerLinkTag()
     */
    public array $linkTags = [];

    /**
     * @var array Registered css blocks `<style>`.
     */
    public array $css = [];

    /**
     * @var array Registered .css files.
     */
    public array $cssFiles = [];

    /**
     * @var array Registered JavaScript code fragments.
     */
    public array $js = [];

    /**
     * @var array Registered .js files.
     */
    public array $jsFiles = [];

    /**
     * @var AssetBundle[] Associative array of registered asset bundles.
     * As 'bundle class name' => bundle object, instance of AssetBundle
     * @see registerAssetBundle()
     */
    public array $assetBundles = [];

    /**
     * @var string Page layout.
     */
    protected string $layout;

    /**
     * @var string Site root path. To find templates with absolute path.
     * @see findTemplate()
     */
    protected string $rootPath;

    /**
     * @var string Default directory where templates located.
     * @see $viewsPath
     */
    protected string $path;

    /**
     * @var string Default directory where page layouts located..
     * @see $viewsPath
     */
    protected string $layoutsPath;

    /**
     * @var ViewHandlerInterface[] Rendered content handlers
     * @see addHandler()
     */
    protected array $handlers = [];

    /**
     * @var array Associative array of registered variables, as pairs 'name' => $value
     * @see assign()
     */
    protected array $assigned = [];

    /**
     * @var string Rendered template contend. To be placed into page layout.
     */
    protected string $renderedTemplate;

    /**
     * Constructor.
     * 
     * @param string $layout Default view's page layout.
     * Filename without extension, placed according value of $layoutsPath
     * 
     * @param array $options Additional view properties values in pairs 'prop' => $value
     */
    public function __construct(string $layout, array $options = [])
    {
        $options = array_merge(self::$defaults, $options);
        foreach ($options as $prop => $value) {
            $this->$prop = $value;
        }

        $this->setLayout($layout);
    }

    /**
     * Set page layout.
     * 
     * @param string $layout Path to page layout (relative from default or absolute from site root).
     * 
     * @return void
     */
    public function setLayout(string $layout): void
    {
        $this->layout = $this->findTemplate($layout, $this->layoutsPath);
    }

    /**
     * Set page title
     * 
     * @param string $title
     * 
     * @return self
     */
    public function setTitle(string $title): self
    {
        $this->title = html_entity_decode(trim($title));
        return $this;
    }

    /**
     * Append something to the title of the page
     * 
     * @param string $append
     * 
     * @return self
     */
    public function addToTitle(string $append): self
    {
        $this->title .= html_entity_decode($append);
        return $this;
    }

    /**
     * Prepend something to the title of the page
     * 
     * @param string $prepend
     * 
     * @return self
     */
    public function prependToTitle(string $prepend): self
    {
        $this->title = html_entity_decode($prepend) . $this->title;
        return $this;
    }

    /**
     * Get rendered template
     * 
     * @param string $__template_ Path to template (without extension)
     * @param array $__data_ Template data associative array in pairs 'name' => $value.
     * Will be extract and available as variables in templates.
     * 
     * @return string Rendered content.
     */
    private function fetchFile(string $__template_, array $__data_ = []): string
    {
        $__template_ .= '.php';

        if (!is_file($__template_)) {
            throw new InvalidConfigException("View template '{$__template_}' not found");
        }

        if (!empty($this->assigned)) {
            extract($this->assigned, EXTR_OVERWRITE);
        }

        if (!empty($__data_)) {
            extract($__data_, EXTR_OVERWRITE);
        }

        $__obStartLevel = ob_get_level();
        ob_start();
        ob_implicit_flush(false);
        try {
            require $__template_;
            return ob_get_clean();
        } catch (Throwable $e) {
            while (ob_get_level() > $__obStartLevel) {
                if (!ob_end_clean()) {
                    ob_clean();
                }
            }
            throw $e;
        }
    }

    /**
     * Get rendered layout with inserted template, and with $data parameters.
     * 
     * @param string $template
     * @param array $data
     * 
     * @return string
     */
    private function fetchLayout(string $template, array $data = []): string
    {
        $this->renderedTemplate = $this->fetchFile($template, $data);
        $html = $this->fetchFile($this->layout);
        return $html;
    }

    /**
     * Find template. The path started from '/' considered as absolute from site root.
     * In other cases path considered as relative from $defaultPath parameter.
     * 
     * @param string $templatePath Path to template
     * @param string $defaultPath Default path to relative template paths.
     * 
     * @return string
     */
    private function findTemplate(string $templatePath, string $defaultPath): string
    {
        $templatePath = str_replace('\\', '/', $templatePath);
        $root = $templatePath[0] === '/' ? $this->rootPath : $defaultPath;

        return normalizePath($root . DS . $templatePath);
    }

    /**
     * Run all output handlers.
     * 
     * @param string $html
     * 
     * @return string
     */
    private function handleOutput(string $html): string
    {
        foreach ($this->handlers as $handler) {
            $html = $this->runHandler(new $handler, $html);
        }

        return $html;
    }

    /**
     * Run output handler.
     * 
     * @param ViewHandlerInterface $handler
     * @param string $html
     * 
     * @return string
     */
    private function runHandler(ViewHandlerInterface $handler, string $html): string
    {
        return $handler->handle($html);
    }

    /**
     * Run in view widget.
     * 
     * @param Widget $widget
     * 
     * @return void
     */
    private function runWidget(Widget $widget): void
    {
        echo $widget->execute();
    }

    /**
     * Assign variable to view.
     * 
     * @param string|array $name Name or associative array name=>value (the keys interprets as variables names)
     * @param mixed $value Variable value.
     * 
     * @return self
     */
    public function assign(string|array $name, mixed $value = null): self
    {
        switch (gettype($name)) {
            case 'array':
                if ($value !== null) {
                    trigger_error('Value not assigned to View, because name passed as an array.');
                }
                foreach ($name as $key => $value) {
                    $this->assigned[$key] = $value;
                }
                break;

            case 'string':
                $this->assigned[$name] = $value;
        }

        return $this;
    }

    /**
     * Get assigned variable
     * 
     * @param string  $name Variable name
     * @param mixed $default Default value if variable with given name does not exists
     * 
     * @return mixed Variable value
     */
    public function getAssigned(string $name, mixed $default = null): mixed
    {
        return $this->assigned[$name] ?? $default;
    }

    /**
     * Render template.
     * 
     * @param string $template Path to template
     * @param array $data Template data
     * 
     * @return string
     */
    public function renderPart(string $template, array $data = []): string
    {
        $tplPath = $this->findTemplate($template, $this->path);
        $html = $this->fetchFile($tplPath, $data);
        return $html;
    }

    /**
     * Render template placed in page layout.
     * 
     * @param string $template Path to template
     * @param array $data Template data
     * 
     * @return string
     */
    public function render(string $template, array $data = []): string
    {
        $tplPath = $this->findTemplate($template, $this->path);
        $html = $this->fetchLayout($tplPath, $data);
        $html = $this->handleOutput($html);
        return $html;
    }

    /**
     * Begins recording a block.
     *
     * This method is a shortcut to beginning [[Block]].
     * @param string $id the block ID.
     * @param bool $renderInPlace whether to render the block content in place.
     * Defaults to false, meaning the captured block will not be displayed.
     * 
     * @return Block the Block widget instance
     */
    public function beginBlock(string $id, bool $renderInPlace = false): Block
    {
        return Block::begin([
            'id' => $id,
            'renderInPlace' => $renderInPlace,
            'view' => $this,
        ]);
    }

    /**
     * Ends recording a block.
     * 
     * @return void
     */
    public function endBlock(): void
    {
        Block::end();
    }

    /**
     * Get block content.
     * 
     * @param string $id Block ID.
     * 
     * @return string Block content.
     */
    public function block(string $id): string
    {
        return $this->blocks[$id] ?? '';
    }

    /**
     * Run widget
     * 
     * @param string $class Name of widget class
     * @param mixed $data Widget data
     * 
     * @return void
     */
    public function widget(string $class, array $data = []): void
    {
        $widget = new $class($this, $data);
        $this->runWidget($widget);
    }

    /**
     * Add output handler.
     * 
     * @param string $class Output handler class name
     * 
     * @return self
     */
    public function addHandler(string $class): self
    {
        $this->handlers[] = $class;
        return $this;
    }

    /**
     * Register asset bundle.
     * 
     * @param string $name Bundle class name
     * @param int|null $position Bundle's tag place
     * 
     * @return AssetBundle registered asset bundle
     * @see POS_* constants
     */
    public function registerAssetBundle(string $name, int $position = null): AssetBundle
    {
        if (isset($this->assetBundles[$name])) {
            $bundle = $this->assetBundles[$name];
        } else {
            $bundle = new $name;
            if ($bundle->depends) {
                foreach ($bundle->depends as $dep) {
                    if (in_array($name, (new $dep())->depends)) {
                        throw new InvalidConfigException(
                            sprintf('A circular dependency is detected for bundle %s', $name)
                        );
                    }
                    $this->registerAssetBundle($dep);
                }
            }

            $this->assetBundles[$name] = $bundle;
        }

        if ($position !== null) {
            $bundle->jsOptions['position'] = $position;
        }

        return $bundle;
    }

    /**
     * Register javascript file.
     * 
     * @param string $source
     * @param array $options
     * @param string $key
     * 
     * @return self
     * @see registerFile()
     */
    public function registerJsFile(string $source, array $options = [], string $key = ''): self
    {
        $this->registerFile('js', $source, $options, $key);

        return $this;
    }

    /**
     * Register css file.
     * 
     * @param string $source
     * @param array $options
     * @param string $key
     * 
     * @return self
     * @see registerFile()
     */
    public function registerCssFile(string $source, array $options = [], string $key = ''): self
    {
        $this->registerFile('css', $source, $options, $key);

        return $this;
    }

    /**
     * Registers a JS code block defining a variable. The name of variable will be
     * used as key, preventing duplicated variable names.
     *
     * @param string $name Name of the variable
     * @param mixed $value Value of the variable
     * @param int $position the position in a page at which the JavaScript variable should be inserted.
     * The possible values are:
     *
     * - POS_HEAD: in the head section. This is the default value.
     * - POS_BODY_BEGIN: at the beginning of the body section.
     * - POS_BODY_END: at the end of the body section.
     * - POS_LOAD: enclosed within jQuery(window).load().
     * - POS_READY: enclosed within jQuery(document).ready().
     * 
     * @return self
     */
    public function registerJsVar(string $name, mixed $value, int $position = self::POS_HEAD): self
    {
        $js = sprintf('var %s = %s;', $name, Json::htmlEncode($value));
        $this->registerJs($js, $position, $name);

        return $this;
    }

    /**
     * Register .js or .css file
     * 
     * @param string $source File source web path.
     * @param string $type 'js' or 'css'.
     * @param array $options HTML tag attributes.
     * @param string $key ID to prevent same files register. If not specified the `$source` value will be used.
     */
    private function registerFile(string $type, string $source, array $options = [], string $key = ''): void
    {
        $key = $key ?: $source;
        $position = ArrayHelper::remove($options, 'position', static::POS_HEAD);

        switch ($type) {
            case AssetReg::JS:
                $this->jsFiles[$position][$key] = AssetReg::getTag($source, AssetReg::JS, $options);
                break;

            case AssetReg::CSS:
                $this->cssFiles[$key] = AssetReg::getTag($source, AssetReg::CSS, $options);
                break;
        }
    }

    /**
     * Registers all files provided by an asset bundle including depending bundles files.
     * Removes a bundle from `$assetBundles` once files are registered.
     * @param string $name name of the bundle to register
     */
    private function registerAssetFiles(string $name): void
    {
        if (!isset($this->assetBundles[$name])) {
            return;
        }
        /** @var AssetBundle $bundle */
        $bundle = $this->assetBundles[$name];
        if ($bundle) {
            foreach ($bundle->depends as $dep) {
                /** @var string $dep */
                $this->registerAssetFiles($dep);
            }
            $bundle->registerAssetFiles($this);
        }
        unset($this->assetBundles[$name]);
    }

    /**
     * Registers a CSS code block.
     * @param string $css the content of the CSS code block to be registered
     * @param string|array $options the HTML attributes for the `<style>`-tag.
     * @param string $key the key that identifies the CSS code block. If null, it will use
     * $css as the key. If two CSS code blocks are registered with the same key, the latter
     * will overwrite the former.
     * 
     * @return self
     */
    public function registerCss(string $css, array $options = [], string $key = ''): self
    {
        $key = $key ?: md5($css);
        $this->css[$key] = [$css, $options];

        return $this;
    }

    /**
     * Registers a JS code block.
     * @param string $js the JS code block to be registered
     * @param int $position the position at which the JS script tag should be inserted
     * in a page. The possible values are:
     *
     * - POS_HEAD
     * - POS_BODY_BEGIN
     * - POS_BODY_END
     * - POS_LOAD
     * - POS_READY
     *
     * @param string $key the key that identifies the JS code block. If null, it will use
     * $js as the key. If two JS code blocks are registered with the same key, the latter
     * will overwrite the former.
     * 
     * @return self
     */
    public function registerJs(string $js, int $position = self::POS_READY, string $key = ''): self
    {
        $key = $key ?: md5($js);
        $this->js[$position][$key] = $js;

        return $this;
    }

    /**
     * Registers a meta tag.
     *
     * For example, a description meta tag can be added like the following:
     *
     * ```php
     * $view->registerMetaTag([
     *     'name' => 'description',
     *     'content' => 'This website is about funny raccoons.'
     * ]);
     * ```
     *
     * will result in the meta tag `<meta name="description" content="This website is about funny raccoons.">`.
     *
     * @param string|array $options the HTML attributes for the meta tag.
     * @param string $key the key that identifies the meta tag. If two meta tags are registered
     * with the same key, the latter will overwrite the former. If this is null, the new meta tag
     * will be appended to the existing ones.
     * 
     * @return self
     */
    public function registerMetaTag(array $options, string $key = ''): self
    {
        if ($key) {
            $this->metaTags[$key] = Html::tag('meta', '', $options);
        } else {
            $this->metaTags[] = Html::tag('meta', '', $options);
        }

        return $this;
    }

    /**
     * Registers CSRF meta tags.
     * 
     * ```php
     * $view->registerCsrfMetaTags('_csrf-frontend', '=csrftokenexample');
     * ```
     * The above code will result in
     * <meta name="csrf-param" content="_csrf-frontend"> and
     * <meta name="csrf-token" content="=csrftokenexample"> added to the page.
     * 
     * Note: Hidden CSRF input of ActiveForm will be automatically refreshed by calling window.sys.refreshCsrfToken() from sys.js.
     * 
     * @param string $name Csrf paremeter name
     * @param string $token Csrf token
     * 
     * @return self
     */
    public function registerCsrfMetaTag(string $name, string $token): self
    {
        $this
            ->registerMetaTag([
                'name' => 'csrf-param',
                'content' => $name
            ])
            ->registerMetaTag([
                'name' => 'csrf-token',
                'content' => $token
            ]);

        return $this;
    }

    /**
     * Registers a link tag.
     *
     * For example, a link tag for a custom [favicon](http://www.w3.org/2005/10/howto-favicon)
     * can be added like the following:
     *
     * ```php
     * $view->registerLinkTag(['rel' => 'icon', 'type' => 'image/png', 'href' => '/myicon.png']);
     * ```
     *
     * which will result in the following HTML: `<link rel="icon" type="image/png" href="/myicon.png">`.
     *
     * **Note:** To register link tags for CSS stylesheets, use [[registerCssFile()]] instead, which
     * has more options for this kind of link tag.
     *
     * @param array $options the HTML attributes for the link tag.
     * @param string $key the key that identifies the link tag. If two link tags are registered
     * with the same key, the latter will overwrite the former. If this is null, the new link tag
     * will be appended to the existing ones.
     * 
     * @return self
     */
    public function registerLinkTag(array $options, string $key = ''): self
    {
        $tag = Html::tag('link', '', $options);
        if ($key) {
            $this->linkTags[$key] = $tag;
        } else {
            $this->linkTags[] = $tag;
        }

        return $this;
    }

    /**
     * Renders the content to be inserted in the head section.
     * The content is rendered using the registered meta tags, link tags, CSS/JS code blocks and files.
     * @return string the rendered content
     */
    protected function renderHeadHtml(): string
    {
        $lines = [];
        if (!empty($this->metaTags)) {
            $lines[] = implode("\n", $this->metaTags);
        }

        if (!empty($this->linkTags)) {
            $lines[] = implode("\n", $this->linkTags);
        }
        if (!empty($this->cssFiles)) {
            $lines[] = implode("\n", $this->cssFiles);
        }
        if (!empty($this->css)) {
            $lines[] = implode("\n", $this->css);
        }
        if (!empty($this->jsFiles[self::POS_HEAD])) {
            $lines[] = implode("\n", $this->jsFiles[self::POS_HEAD]);
        }
        if (!empty($this->js[self::POS_HEAD])) {
            $lines[] = Html::script(implode("\n", $this->js[self::POS_HEAD]));
        }

        return empty($lines) ? '' : implode("\n", $lines) . "\n";
    }

    /**
     * Renders the content to be inserted at the beginning of the body section.
     * The content is rendered using the registered JS code blocks and files.
     * @return string the rendered content
     */
    protected function renderBodyBeginHtml(): string
    {
        $lines = [];
        if (!empty($this->jsFiles[self::POS_BODY_BEGIN])) {
            $lines[] = implode("\n", $this->jsFiles[self::POS_BODY_BEGIN]);
        }
        if (!empty($this->js[self::POS_BODY_BEGIN])) {
            $lines[] = Html::script(implode("\n", $this->js[self::POS_BODY_BEGIN]));
        }

        return empty($lines) ? '' : implode("\n", $lines) . "\n";
    }

    /**
     * Renders the content to be inserted at the end of the body section.
     * The content is rendered using the registered JS code blocks and files.
     * @param bool $ajaxMode whether the view is rendering in AJAX mode.
     * If true, the JS scripts registered at [[POS_READY]] and [[POS_LOAD]] positions
     * will be rendered at the end of the view like normal scripts.
     * @return string the rendered content
     */
    protected function renderBodyEndHtml(bool $ajaxMode = false): string
    {
        $lines = [];

        if (!empty($this->jsFiles[self::POS_BODY_END])) {
            $lines[] = implode("\n", $this->jsFiles[self::POS_BODY_END]);
        }

        if ($ajaxMode) {
            $scripts = [];
            if (!empty($this->js[self::POS_BODY_END])) {
                $scripts[] = implode("\n", $this->js[self::POS_BODY_END]);
            }
            if (!empty($this->js[self::POS_READY])) {
                $scripts[] = implode("\n", $this->js[self::POS_READY]);
            }
            if (!empty($this->js[self::POS_LOAD])) {
                $scripts[] = implode("\n", $this->js[self::POS_LOAD]);
            }
            if (!empty($scripts)) {
                $lines[] = Html::script(implode("\n", $scripts));
            }
        } else {
            if (!empty($this->js[self::POS_BODY_END])) {
                $lines[] = Html::script(implode("\n", $this->js[self::POS_BODY_END]));
            }
            if (!empty($this->js[self::POS_READY])) {
                $js = "jQuery(function ($) {\n" . implode("\n", $this->js[self::POS_READY]) . "\n});";
                $lines[] = Html::script($js);
            }
            if (!empty($this->js[self::POS_LOAD])) {
                $js = "jQuery(window).on('load', function () {\n" . implode("\n", $this->js[self::POS_LOAD]) . "\n});";
                $lines[] = Html::script($js);
            }
        }

        return empty($lines) ? '' : implode("\n", $lines) . "\n";
    }

    /**
     * Marks the position of an HTML head section.
     */
    public function head(): void
    {
        echo self::PH_HEAD;
    }

    /**
     * Marks the beginning of a page.
     */
    public function beginPage(): void
    {
        ob_start();
        ob_implicit_flush(false);
    }

    /**
     * Marks the ending of an HTML page.
     * @param bool $ajaxMode whether the view is rendering in AJAX mode.
     * If true, the JS scripts registered at [[POS_READY]] and [[POS_LOAD]] positions
     * will be rendered at the end of the view like normal scripts.
     */
    public function endPage(bool $ajaxMode = false): void
    {
        $content = ob_get_clean();

        echo strtr($content, [
            self::PH_HEAD => $this->renderHeadHtml(),
            self::PH_BODY_BEGIN => $this->renderBodyBeginHtml(),
            self::PH_BODY_END => $this->renderBodyEndHtml($ajaxMode),
        ]);

        $this->clear();
    }

    /**
     * Marks the beginning of an HTML body section.
     */
    public function beginBody(): void
    {
        echo self::PH_BODY_BEGIN;
    }

    /**
     * Marks the ending of an HTML body section.
     */
    public function endBody(): void
    {
        echo self::PH_BODY_END;

        foreach (array_keys($this->assetBundles) as $bundle) {
            $this->registerAssetFiles($bundle);
        }
    }

    /**
     * Clears up the registered meta tags, link tags, css/js scripts and files.
     */
    public function clear(): void
    {
        $this->metaTags = [];
        $this->linkTags = [];
        $this->css = [];
        $this->cssFiles = [];
        $this->js = [];
        $this->jsFiles = [];
        $this->assetBundles = [];
    }
}