<?php

declare(strict_types=1);

namespace common\widgets;

use kartik\typeahead\Typeahead;
use core\activeform\ActiveForm;
use core\activeform\JsExpression;
use core\helpers\Html;
use core\helpers\Url;

/**
 * Widget for search on site.
 */
class SearchSite extends \core\widgets\Widget
{
    /**
     * @var string Suggest values url
     */
    public string $suggestUrl;

    /**
     * @var string Search form action
     */
    public string $action = '';

    /**
     * @var string Search form id
     */
    public string $formId = 'fastSiteSearchForm';

    /**
     * @var string Search input placeholder
     */
    public string $placeholder = '';

    /**
     * @var int Minimal symbols to enter to suggestions appears.
     */
    public int $minLength = 3;

    /**
     * @var string Search input ID
     */
    public string $id = 'searchFastOnSite';

    /**
     * @var string Search input name
     */
    public string $name = 'search';

    public function run()
    {
        $this->registerScript();

        ActiveForm::begin([
            'method' => 'get',
            'action' => $this->action,
            'options' => [
                'class' => 'typeahead-search-group submit-default',
                'id' => $this->formId,
            ]
        ]);

        echo Typeahead::widget($this->getOptions())
            . Html::tag('span', '<i class="fas fa-fw fa-times"></i>', [
                'id' => 'searchPartButtonReset',
                'title' => 'Очистить',
                'style' => $this->getRequest()->get($this->name) ? false : 'display:none;',
            ])
            . Html::submitButton('<i class="fas fa-fw fa-search"></i>', [
                'class' => 'btn btn-secondary',
            ]);

        ActiveForm::end();
    }

    public function getOptions()
    {
        $template = '<div>' .
            '<p class="py-1">{{value}}</p>' .
            '<p class="text-muted small mb-1">{{description}}</p></div>';

        $options = [
            'name' => $this->name,
            'id' => $this->id,
            'value' => $this->getRequest()->get($this->name, ''),
            'options' => [
                'placeholder' => $this->placeholder,
                'autocomplete' => 'off'
            ],
            'pluginOptions' => [
                'highlight' => true,
                'minLength' => $this->minLength,
            ],
            'pluginEvents' => [
                'typeahead:select' => "(e) => {
                    e.target.form.submit();
                }",
            ],
            'dataset' => [
                [
                    'datumTokenizer' => "Bloodhound.tokenizers.obj.whitespace('value')",
                    'display' => 'value',
                    'limit' => 10,
                    'remote' => [
                        'url' => Url::to([$this->suggestUrl]) . '?query=%QUERY',
                        'wildcard' => '%QUERY'
                    ],
                    'templates' => [
                        'notFound' => '<div class="text-danger px-3 py-1">' . t('Ничего не найдено') . '</div>',
                        'suggestion' => new JsExpression("Handlebars.compile('{$template}')")
                    ]
                ]
            ],
        ];

        return $options;
    }

    public function registerScript()
    {
        // Reset button behaviour.
        // If search query exist - just reload current url without `$name` (generally 'search') parameter
        // If no search query - just clear search input
        $this->getView()->registerJs("jQuery('html').on('click', '#searchPartButtonReset', function() {
            if (getQueryValue(window.location.href, '{$this->name}') === null) {
                $('input#{$this->id}').val('');
                $(this).hide();
            } else {
                window.location.href = removeQueryValue(window.location.href, '{$this->name}');
            }
        });");

        // Show reset button when search input not empty
        $this->getView()->registerJs("jQuery('html').on('input', 'input#{$this->id}', function() {
            $(this).val().length ? $('#searchPartButtonReset').show() : $('#searchPartButtonReset').hide();
        });");

        // Hide status profile counters if search string not empty.
        // Search result is assumed to be shown.
        $this->getView()->registerJs("
        if ($('input#{$this->id}').val().length) {
            $('.profiler-count').hide();
        }");

        // Hide suggestions on submit
        $this->getView()->registerJs("
        $('html').on('submit', 'form#{$this->formId}', () => {
            $('#{$this->id}').typeahead('close');
        });");
    }
}
