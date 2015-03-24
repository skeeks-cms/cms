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
use skeeks\cms\base\Widget;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * Class ActiveForm
 * @package skeeks\cms\widgets\base\hasModelsSmart
 */
class ActiveForm extends \skeeks\cms\widgets\base\hasTemplate\ActiveForm
{
    public function standartElements(Widget $model)
    {
        $widget = $model;
        $entity  = new $widget->modelClassName;

        /**
         * @var $entity ActiveRecord;
         */

        $this->templateElement($model);

        echo $this->fieldSet('Настройки');
            echo $this->field($model, 'applySearchParams')->label('Зависит от GET и POST параметров')->hint('Если да то записи будут фильтроваться согласно параметрам')->widget(
            \skeeks\widget\chosen\Chosen::className(), [
                'items'   => [
                    '0' => 'нет',
                    '1' => 'да',
                ],
            ]);
            ;



            echo $this->field($model, 'limit')->label('Максимальное количество записей')->hint('Если не будет указано будут выбраны все записи')->textInput();


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

        echo $this->fieldSet('Настройки постраничной навигации');

            echo $this->field($model, 'usePaging')->label('Включить постраничную навигацию')->hint('Если да то список публикаций можно показывать с постраничной навигацией')->widget(
                \skeeks\widget\chosen\Chosen::className(), [
                    'items'   => [
                        '0' => 'нет',
                        '1' => 'да',
                    ],
                ]);
                ;

            echo $this->field($model, 'defaultPageSize')->label('Количество записей на странице')->textInput();
            echo $this->field($model, 'pageParam')->label('Название параметра')->textInput();

            echo $this->field($model, 'enablePjaxPagination')->widget(
                \skeeks\widget\chosen\Chosen::className(), [
                    'items'   => [
                        '0' => 'нет',
                        '1' => 'да',
                    ],
                ]);
                ;
        echo $this->fieldSetEnd();

    }


}