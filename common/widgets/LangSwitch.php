<?php

declare(strict_types=1);

namespace common\widgets;

use core\helpers\Url;
use core\helpers\Html;
use core\http\Uri;

/**
 * Language switch widget
 */
class LangSwitch extends \core\widgets\Widget
{
    /**
     * @var string Current language.
     */
    public string $currentLang;

    /**
     * @var array Supported languages.
     */
    public array $languages = [];

    /**
     * @var array Links options.
     */
    public array $linkOptions = [];

    /**
     * @var array Text indicator options.
     */
    public array $textOptions = [];

    /**
     * @var array Widget container options.
     */
    public array $options = [];

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        $items = [];
        foreach ($this->languages as $lang) {
            $text = strtoupper($lang);
            if ($lang === $this->currentLang) {
                $item = Html::tag('span', $text, $this->textOptions);
            } else {
                $langUri = Uri::withQueryValue(Url::$uri, 'lang', strtolower($lang));
                $linkOptions = array_merge($this->linkOptions, ['hreflang' => $lang]);
                $item = Html::a($text, Url::getRelative($langUri), $linkOptions);
                $this->getView()->registerLinkTag(['rel' => 'alternate', 'hreflang' => $lang, 'href' => (string) $langUri]);
            }

            $items[] = $item;
        }

        echo Html::ul($items, $this->options);
    }
}