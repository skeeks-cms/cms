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
namespace skeeks\cms\widgets\base\hasModelsSmart;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * Class ActiveForm
 * @package skeeks\cms\widgets\base\hasModelsSmart
 */
class ActiveForm extends \skeeks\cms\widgets\base\hasTemplate\ActiveForm
{
    public function standartElements($model, \skeeks\cms\models\WidgetConfig $widgetConfig)
    {
        $widget = new $widgetConfig->widget;
        $entity  = new $widget->modelClassName;
        /**
         * @var $entity ActiveRecord;
         */

        $this->templateElement($model);

        echo $this->fieldSet('Сортировка и количество записей на странице');
            echo $this->field($model, 'defaultPageSize')->label('Количество записей на странице')->textInput();
            echo $this->field($model, 'defaultSortField')->label('По какому полю сортировать')->widget(
                \skeeks\widget\chosen\Chosen::className(), [
                    'items'   => $entity->attributeLabels(),
                ]);
            echo $this->field($model, 'defaultSort')->label('Направление сортировки')->widget(
            \skeeks\widget\chosen\Chosen::className(), [
                'items'   => [
                    SORT_ASC    => "ASC (от меньшего к большему)",
                    SORT_DESC   => "DESC (от большего к меньшему)",
                ],
            ]);



        echo $this->fieldSetEnd();
    }
}