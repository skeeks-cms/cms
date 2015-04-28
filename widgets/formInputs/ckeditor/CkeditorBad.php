<?php
/**
 * Ckeditor
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 29.01.2015
 * @since 1.0.0
 */
namespace skeeks\cms\widgets\formInputs\ckeditor;

use skeeks\cms\Exception;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\InputWidget;
use Yii;

/**
 * Class Ckeditor
 * @package skeeks\cms\widgets\formInputs\ckeditor
 */
class Ckeditor extends \skeeks\widget\ckeditor\CKEditor
{

    public $callbackImages;

    /**
	 * Registers CKEditor plugin
	 */
	protected function registerPlugin()
	{
        //Пусть оригинальный Ckeditor сделает чего ему надо
        parent::registerPlugin();

        $callback_images = $this->callbackImages;
		$ar_files = array();

        $has_files_behavior = false;
        $behavior_class = 'skeeks\cms\models\behaviors\HasFiles';
        foreach ($callback_images->getBehaviors() as $behavior)
        {
            if ($behavior instanceof $behavior_class)
            {
                $has_files_behavior = true;
            }
        }

		if($has_files_behavior)
		{
			//$files = \skeeks\cms\models\StorageFile::find()->where(['linked_to_value' => $callback_images->id])->all();
            $files = \skeeks\cms\models\StorageFile::find()->where(['linked_to_value' => $callback_images->id, 'type' => 'image'])->all();
            foreach ($files as $file) {
				$ar_files[] = array(
					$file["original_name"],
					$file["src"]
				);
			}
		}

        $bundle = Yii::$app->getAssetManager()->getBundle(Asset::className());
        $icon = \Yii::$app->getAssetManager()->getAssetUrl($bundle, 'imageselect.png');

        $options = Json::encode([
            'files' => $ar_files,
            'icon' => $icon,
        ]);

        $this->getView()->registerJs(<<<JS
            (function(sx, $, _)
            {
                sx.createNamespace('classes', sx);

                sx.classes.CkeditorPlugins = sx.classes.Component.extend({

                    _init: function()
                    {},


                    imageselectPlugin: function()
                    {
                            var imgsel = CKEDITOR.plugins.get('imageselect');
                            var files = this.get('files');
                            var icon = this.get('icon');
                            if(!imgsel) {
                                files.unshift([ '- Не выбран -', '' ]);
                                CKEDITOR.plugins.add('imageselect',
                                    {
                                        init: function (editor) {
                                            editor.addCommand('fileSelectDialog', new CKEDITOR.dialogCommand('fileSelectDialog' ));
                                            editor.ui.addButton('ImageSelect',
                                                {
                                                    label: 'Выбрать привязанное изображение',
                                                    command: 'fileSelectDialog',
                                                    icon: icon
                                                });
                                            CKEDITOR.dialog.add( 'fileSelectDialog', function( editor )
                                            {
                                                return {
                                                    title : 'Выберите изображение',
                                                    minWidth : 400,
                                                    minHeight : 200,
                                                    contents :
                                                        [
                                                            {
                                                                id : 'general',
                                                                label : 'Settings',
                                                                elements :
                                                                    [
                                                                        {
                                                                            type : 'html',
                                                                            html : 'Выберите одно из изображений, привязанных к данному элементу.'
                                                                        },
                                                                        {
                                                                            type : 'select',
                                                                            id : 'file',
                                                                            label : 'Файл:',
                                                                            items : files,
                                                                            commit : function( data )
                                                                            {
                                                                                data.src = this.getValue();
                                                                            },
                                                                            onChange: function()
                                                                            {
                                                                                var dialog = this.getDialog();
                                                                                var eHtml = dialog.getContentElement('general','imagebox').getElement();
                                                                                //var image = editor.document.createElement('image');
                                                                                //image.setAttribute('src', this.getValue());
                                                                                //console.log(image);
                                                                                //var html = image.getText();
                                                                                //console.log(html);
                                                                                var src = this.getValue();
                                                                                if(src)
                                                                                {
                                                                                    var img_html = '<img src="'+this.getValue()+'" style="height: 200px;" />';
                                                                                }
                                                                                else
                                                                                {
                                                                                    var img_html = '';
                                                                                }
                                                                                eHtml.setHtml(img_html);
                                                                            }
                                                                        },
                                                                        {
                                                                            type : 'html',
                                                                            id : 'imagebox',
                                                                            html : ''
                                                                        }
                                                                    ]
                                                            }
                                                        ],
                                                        onOk : function()
                                                        {
                                                            var dialog = this,
                                                                data = {},
                                                                image = editor.document.createElement('img');

                                                            this.commitContent(data);

                                                            image.setAttribute('src', data.src );

                                                            editor.insertElement(image);
                                                        }
                                                };
                                            });
                                        }
                                    });
                            }
                    },

                    _onDomReady: function()
                    {
                        var self = this;
                        self.imageselectPlugin();

                    },

                    _onWindowReady: function()
                    {}
                });

                new sx.classes.CkeditorPlugins({$options});

            })(sx, sx.$, sx._);
JS
);
	}
}
