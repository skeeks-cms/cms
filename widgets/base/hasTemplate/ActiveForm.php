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

/**
 * Class ActiveForm
 * @package skeeks\cms\widgets\widgetHasTemplate
 */
class ActiveForm extends \skeeks\cms\modules\admin\widgets\ActiveForm
{
    /**
     * @param $model
     */
    public function templateElement($model)
    {
        echo $this->field($model, 'template')->label('Шаблон')->widget(
            \skeeks\widget\chosen\Chosen::className(),
            [
                'items' => \yii\helpers\ArrayHelper::map(
                     $model->getWidgetDescriptor()->getTemplatesObject()->getComponents(),
                     "id",
                     "name"
                 ),
            ]
        );
    }
}