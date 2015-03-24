<?php
/**
 * ActiveForm
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 02.02.2015
 * @since 1.0.0
 */
namespace skeeks\cms\widgets\base\hasTemplate;
use skeeks\cms\base\Widget;
use skeeks\cms\modules\admin\widgets\form\ActiveFormStyled;
use skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab;
use yii\helpers\Html;
use yii\helpers\Json;

/**
 * Class ActiveForm
 * @package skeeks\cms\widgets\widgetHasTemplate
 */
class ActiveForm extends ActiveFormUseTab
{
    /**
     * @param $model
     */
    public function templateElement(Widget $model)
    {
        echo $this->fieldSet('Настройки отображения');

        echo $this->field($model, 'viewFile')->textInput()->hint('Вы можете указать путь к файлу шаблона принудительно');

        echo $this->field($model, 'template')->label('Шаблон')->widget(
            \skeeks\widget\chosen\Chosen::className(),
            [
                'items' => \yii\helpers\ArrayHelper::map(
                     $model->getDescriptor()->getTemplatesObject()->getComponents(),
                     "id",
                     "name"
                 ),
            ]
        );

        $options = Json::encode([
            'id-viewFile' => Html::getInputId($model, 'viewFile'),
            'id-template' => Html::getInputId($model, 'template')
        ]);

        $this->view->registerJs(<<<JS
        (function(sx, $, _)
        {
            sx.classes.FormViewElement = sx.classes.Component.extend({

                _init: function()
                {},

                _onDomReady: function()
                {
                    var self = this;
                    $("#" + this.get('id-viewFile')).on('keyup', function()
                    {
                        self.update();
                    });

                    this.update();
                },

                update: function()
                {
                    if ($("#" + this.get('id-viewFile')).val())
                    {
                        $(".field-" + this.get('id-template')).hide();
                    }
                },

                _onWindowReady: function()
                {}
            });

            new sx.classes.FormViewElement($options);
        })(sx, sx.$, sx._);
JS
);

        echo $this->fieldSetEnd();
    }
}