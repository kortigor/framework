<?php

declare(strict_types=1);

namespace customer\helpers;

use core\base\Model;
use core\activeform\ActiveForm;
use core\web\View;
use core\helpers\Html;

/**
 * Internationalization helper
 */
class I18nHelper
{
    /**
     * Render entity edit form parts for different languages.
     * 
     * @param string $template Template to render language part
     * @param Model $model Form model to use in template
     * @param ActiveForm $form Form build object to use in template
     * @param View $view View object instance to render
     * 
     * @return array Bootstrap tabs items definitions.
     */
    public static function getEditTabsItems(string $template, Model $model, ActiveForm $form, View $view): array
    {
        foreach (c('main.language.supported') as $lang) {
            $tabs[] = [
                'label' => Html::tag('span', mb_strtoupper($lang), ['class' => 'badge badge-secondary']),
                'content' => $view->renderPart($template, compact('model', 'lang', 'form')),
            ];
        }

        return $tabs ?? [];
    }
}
