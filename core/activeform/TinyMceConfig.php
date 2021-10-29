<?php

declare(strict_types=1);

namespace core\activeform;

use core\web\AssetReg;
use core\bootstrap4\BootstrapAsset;
use common\assets\FontawesomeAsset;

/**
 * TinyMCE config container.
 *
 * @author Kort Igor <kort.igor@gmail.com> 
 */
class TinyMceConfig
{
    protected const ADVANCED = [

        'plugins' => [
            'autosave advlist autolink lists link image charmap print preview hr anchor pagebreak',
            'searchreplace wordcount visualblocks visualchars code fullscreen',
            'insertdatetime media nonbreaking save table contextmenu directionality',
            'emoticons template paste textcolor colorpicker textpattern moxiemanager imageresizing abbr'
        ],

        'toolbar1' => 'undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent ',
        'toolbar2' => 'searchreplace | blockquote pagebreak emoticons | forecolor backcolor |
         code preview fullscreen | link unlink | media image insertfile insertimage abbr',

        'image_class_list' => [
            ['title' => 'Нет', 'value' => ''],
            ['title' => 'Блочно', 'value' => 'd-block'],
            ['title' => 'Растягивающийся', 'value' => 'img-fluid'],
            ['title' => 'К левому краю (обтекание текстом справа, float:left)', 'value' => 'float-left mr-3'],
            ['title' => 'К правому краю (обтекание текстом слева, float:right)', 'value' => 'float-right ml-3']
        ],

        'style_formats' => [
            [
                'title' => 'Заголовки',
                'items' => [
                    // ['title' => 'Заголовок H1', 'block' => 'h1'],
                    ['title' => 'Заголовок H2', 'block' => 'h2'],
                    ['title' => 'Заголовок H3', 'block' => 'h3'],
                    ['title' => 'Заголовок H4', 'block' => 'h4'],
                    ['title' => 'Заголовок H5', 'block' => 'h5'],
                    ['title' => 'Заголовок H6', 'block' => 'h6'],
                ]
            ],
            [
                'title' => 'Блоки',
                'items' => [
                    // ['title' => 'Адрес <address>', 'block' => 'address'],
                    ['title' => 'Преформатированный <pre>', 'block' => 'pre'],
                    [
                        'title' => 'К левому краю (float: left)',
                        'selector' => 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li,table,img,pre',
                        'classes' => 'float-left mr-3'
                    ],
                    [
                        'title' => 'К правому краю (float: right)',
                        'selector' => 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li,table,img,pre',
                        'classes' => 'float-right ml-3'
                    ],
                ]
            ],
            [
                'title' => 'Размер шрифта',
                'items' => [
                    ['title' => 'x 0.8', 'inline' => 'span', 'styles' => ['font-size' => '.8rem']],
                    ['title' => 'x 1.2', 'inline' => 'span', 'styles' => ['font-size' => '1.2rem']],
                    // ['title' => 'x 1.5', 'inline' => 'span', 'styles' => ['font-size' => '1.5rem']],
                    // ['title' => 'x 2.0', 'inline' => 'span', 'styles' => ['font-size' => '2rem']],
                    // ['title' => '8px', 'inline' => 'span', 'styles' => ['font-size' => '8px']],
                    // ['title' => '10px', 'inline' => 'span', 'styles' => ['font-size' => '10px']],
                    // ['title' => '12px', 'inline' => 'span', 'styles' => ['font-size' => '12px']],
                    // ['title' => '14px', 'inline' => 'span', 'styles' => ['font-size' => '14px']],
                    // ['title' => '16px', 'inline' => 'span', 'styles' => ['font-size' => '16px']],
                    // ['title' => '18px', 'inline' => 'span', 'styles' => ['font-size' => '18px']],
                    // ['title' => '20px', 'inline' => 'span', 'styles' => ['font-size' => '20px']],
                    // ['title' => '22px', 'inline' => 'span', 'styles' => ['font-size' => '22px']],
                    // ['title' => '24px', 'inline' => 'span', 'styles' => ['font-size' => '24px']],
                    // ['title' => '26px', 'inline' => 'span', 'styles' => ['font-size' => '26px']],
                    // ['title' => '28px', 'inline' => 'span', 'styles' => ['font-size' => '28px']],
                    // ['title' => '30px', 'inline' => 'span', 'styles' => ['font-size' => '30px']],
                    // ['title' => '32px', 'inline' => 'span', 'styles' => ['font-size' => '32px']],
                    // ['title' => '34px', 'inline' => 'span', 'styles' => ['font-size' => '34px']],
                    // ['title' => '36px', 'inline' => 'span', 'styles' => ['font-size' => '36px']],
                ]
            ],
            ['title' => 'Абзац', 'block' => 'p'],
            ['title' => 'Текст без переносов', 'inline' => 'span', 'classes' => 'text-nowrap'],
            // ['title' => 'Уменьшенный', 'inline' => 'span', 'classes' => 'small'],
            ['title' => 'Цитата', 'block' => 'p', 'classes' => 'quote'],
            ['title' => 'Ссылка на файл', 'selector' => 'a', 'classes' => 'get-file'],
        ],

        // https://www.tiny.cloud/blog/tinymce-templates/
        // https://www.tiny.cloud/docs/plugins/opensource/template/
        'template_replace_values' => [
            'figureimg' => '/img/example.png',
            'figurecaption' => 'Подпись...',
            'announceimg' => '/img/example150px.png',
            'announce' => 'Этот текст надо исправить!',
        ],
        'template_preview_replace_values' => [
            'figureimg' => '/img/example.png',
            'figurecaption' => 'Подпись...',
            'announceimg' => '/img/example150px.png',
            'announce' => 'Небольшой текст анонса. Небольшой текст анонса.
                Небольшой текст анонса. Небольшой текст анонса.
                Небольшой текст анонса. Небольшой текст анонса.
                Небольшой текст анонса. Небольшой текст анонса.',
        ],
        'templates' => [
            [
                'title' => 'Изображение с подписью (тег <figure>)',
                'description' => 'Вставить шаблон, отредактировать изображение и подпись.',
                'content' => '<figure><img src="{$figureimg}">
                <figcaption>{$figurecaption}</figcaption>
                </figure><p></p>',
            ],
            [
                'title' => 'Анонс с изображением',
                'description' => 'Вставить шаблон, отредактировать изображение и текст.',
                'content' => '<p><img src="{$announceimg}" class="float-left mr-3">
                {$announce}</p>',
            ],
        ],

        'height' => 200,
        'visualblocks_default_state' => true,
        'autosave_prefix' => 'tinymce-autosave-{path}{query}-{id}-user-',
        'insertdatetime_formats' => ['%H:%M:%S', '%d-%m-%Y'],
        'code_dialog_height' => 700,
        'code_dialog_width' => 850,
        'plugin_preview_height' => 700,
        'plugin_preview_width' => 850,
        // 'invalid_elements' => 'script,object,applet',
        'invalid_elements' => 'object,applet',
        'extended_valid_elements' => 'i[class],span[class|style],script[language|type|src]',
        'image_advtab' => true,
        // 'image_dimensions' => false, // disable image width and height settings
        'media_alt_source' => false,
        'media_poster' => false,
        'media_live_embeds' => true,
        'relative_urls' => false,
        'moxiemanager_image_template' => '<a href="{$url}" data-fancybox="gallery"><img src="{$meta.thumb_url}"></a>',
        // https://www.moxiemanager.com/documentation/index.php/moxiemanager_file_template
        'moxiemanager_file_template' => '<a class="get-file" href="{$url}">{$name} | {$size|sizeSuffix}</a>',
        'moxiemanager_view' => 'thumbs',
        'moxiemanager_image_settings' => [
            'view' => 'thumbs',
            'title' => 'Изображения',
        ],
        'moxiemanager_file_settings' => [
            'view' => 'thumbs',
            'title' => 'File Management',
        ],
        'moxiemanager_media_settings' => [
            'view' => 'thumbs',
            'title' => 'Видео',
        ],
        'moxiemanager_title' => 'Файлы',
        'moxiemanager_remember_last_path' => true,
    ];

    protected const SIMPLE = [
        'plugins' => [
            "advlist autolink lists link charmap print preview anchor",
            "searchreplace visualblocks code fullscreen",
            "insertdatetime media table contextmenu paste"
        ],
        'toolbar' => "undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image"
    ];

    /**
     * Get advanced config.
     * 
     * @param array $options TinyMCE options to add
     * 
     * @return array
     */
    public static function advanced(array $options = []): array
    {
        return static::createConfig(static::ADVANCED, $options);
    }

    /**
     * Get simple config.
     * 
     * @param array $options TinyMCE options to add
     * 
     * @return array
     */
    public static function simple(array $options = []): array
    {
        return static::createConfig(static::SIMPLE, $options);
    }

    /**
     * Create config data.
     * 
     * @param array $config Base config data.
     * @param array $options Additional options.
     * 
     * @return array
     */
    protected static function createConfig(array $config, array $options): array
    {
        $config = static::addOptions($config, $options);
        $config = static::addCss($config);
        return $config;
    }

    /**
     * Add options to config.
     * 
     * @param array $config Config.
     * @param array $options Options to add.
     * 
     * @return array
     */
    protected static function addOptions(array $config, array $options): array
    {
        foreach ($options as $key => $value) {
            $config[$key] = $value;
        }

        return $config;
    }

    /**
     * Add necessary css to config.
     * 
     * @param array $config Config.
     * 
     * @return array
     */
    protected static function addCss(array $config): array
    {
        foreach (static::fontAwesomeCss() as $faCss) {
            $config['content_css'][] = AssetReg::appendSrcVersionParam($faCss);
        }

        foreach (static::bootstrapCss() as $bsCss) {
            $config['content_css'][] = AssetReg::appendSrcVersionParam($bsCss);
        }

        $config['content_css'][] = AssetReg::appendSrcVersionParam('/css/publications.min.css');

        return $config;
    }

    /**
     * Get bootstrap css path.
     * 
     * @return array
     */
    protected static function bootstrapCss(): array
    {
        return (new BootstrapAsset)->css;
    }

    /**
     * Get FontAwesome css path.
     * 
     * @return array
     */
    protected static function fontAwesomeCss(): array
    {
        return (new FontawesomeAsset)->css;
    }
}