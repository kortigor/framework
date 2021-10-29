<?php

declare(strict_types=1);

namespace core\activeform;

use core\helpers\Json;
use core\helpers\Html;
use common\assets\TinyMceAsset;

/**
 * TinyMCE renders a tinyMCE js plugin for WYSIWYG editing.
 *
 * @author Antonio Ramirez <amigo.cobos@gmail.com> 
 * @author Kort Igor <kort.igor@gmail.com> 
 */
class TinyMce extends InputWidget
{
    /**
     * @var string the language to use. Defaults to 'ru'.
     */
    public string $language = 'ru';
    /**
     * @var array the options for the TinyMCE JS plugin.
     * Please refer to the TinyMCE JS plugin Web page for possible options.
     * @see http://www.tinymce.com/wiki.php/Configuration
     */
    public array $clientOptions = [];
    /**
     * @var bool whether to set the on change event for the editor. This is required to be able to validate data.
     * @see https://github.com/2amigos/yii2-tinymce-widget/issues/7
     */
    public bool $triggerSaveOnBeforeValidateForm = true;

    /**
     * @inheritdoc
     */
    public function run()
    {
        if ($this->hasModel()) {
            echo Html::activeTextarea($this->model, $this->attribute, $this->options);
        } else {
            echo Html::textarea($this->name, $this->value, $this->options);
        }
        $this->registerClientScript();
    }

    /**
     * Registers tinyMCE js plugin
     */
    protected function registerClientScript()
    {

        $js = [];
        $view = $this->getView();
        $id = $this->options['id'];
        TinyMceAsset::register($view);
        $this->clientOptions['selector'] = "#$id";
        $this->clientOptions['language'] = $this->language; // Language fix. Without it EN language when add some plugins like codemirror 

        $options = Json::encode($this->clientOptions);

        $js[] = "tinymce.remove('#$id'); tinymce.init($options);";

        $js[] = "tinymce.PluginManager.add('imageresizing', function(editor, url) {
                    editor.on('ObjectResized', function(e) {
                        if (e.target.nodeName === 'IMG') {
                            let selectedImage = tinymce.activeEditor.selection.getNode();
                            tinymce.activeEditor.dom.setStyle(selectedImage,'width', e.width);
                            tinymce.activeEditor.dom.setStyle(selectedImage,'height', e.height);
                            selectedImage.removeAttribute('width');
                            selectedImage.removeAttribute('height');
                        }
                    });
                    
                    editor.on('ObjectSelected', function(e) {
                        if (e.target.nodeName === 'IMG') {
                            let selectedImage = tinymce.activeEditor.selection.getNode(),
                                imgWidth = tinymce.activeEditor.dom.getAttrib(selectedImage,'width'),
                                imgHeight = tinymce.activeEditor.dom.getAttrib(selectedImage,'height');

                            if (imgWidth && imgHeight) {
                                tinymce.activeEditor.dom.setStyle(selectedImage, 'width', parseInt(imgWidth));
                                tinymce.activeEditor.dom.setStyle(selectedImage, 'height', parseInt(imgHeight));
                                
                                selectedImage.removeAttribute('width');
                                selectedImage.removeAttribute('height');
                            }
                        }
                    });
        });";

        // if ($this->triggerSaveOnBeforeValidateForm) {
        //     $js[] = "$('#{$id}').parents('form').on('beforeValidate', function() { tinymce.triggerSave(); });";
        // }

        $view->registerJs(implode("\n", $js));
    }
}