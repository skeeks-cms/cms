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
        echo $this->fieldSetEnd();
    }
}