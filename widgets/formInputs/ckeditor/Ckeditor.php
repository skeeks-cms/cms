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

		//TODO $callback_images instanceof \skeeks\cms\models
		//TODO Реализовать для случая, когда $callback_images - не модель
		if(is_object($callback_images))
		{
			$files = \skeeks\cms\models\StorageFile::find()->where(['linked_to_value' => $callback_images->id])->all();
			foreach ($files as $file) {
				$ar_files[] = array(
					$file["original_name"],
					$file["src"]
				);
			}
		}

        $options = Json::encode([
            'files' => $ar_files
        ]);

        $this->getView()->registerJs(<<<JS
            (function(sx, $, _)
            {
                sx.createNamespace('classes', sx);

                sx.classes.CkeditorPlugins = sx.classes.Component.extend({

                    _init: function()
                    {},


                    marenovPlugin: function()
                    {
                            var imgsel = CKEDITOR.plugins.get('imageselect');
                            var files = this.get('files');
                            //console.log(imgsel);
                            if(!imgsel) {
                                files.unshift([ '- Не выбрано -', '' ]);
                                CKEDITOR.plugins.add('imageselect',
                                    {
                                        init: function (editor) {
                                            editor.addCommand('fileSelectDialog', new CKEDITOR.dialogCommand('fileSelectDialog' ));

                                            /*var command = new CKEDITOR.command( editor, {
                                                exec: function( editor ) {
                                                    alert( editor.document.getBody().getHtml() );
                                                }
                                            } );*/

                                            //editor.addCommand('fileSelectDialog', command );
                                            editor.ui.addButton('ImageSelect',
                                                {
                                                    label: 'Выбрать привязанное изображение',
                                                    command: 'fileSelectDialog',
                                                    //icon: scriptSource + '../images/imageselect.png'
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
                                                                            label : 'Файл',
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
                        self.marenovPlugin();

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
