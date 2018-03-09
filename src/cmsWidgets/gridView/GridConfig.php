<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 25.05.2015
 */

namespace skeeks\cms\cmsWidgets\gridView;

use skeeks\cms\base\Component;
use skeeks\yii2\form\fields\FieldSet;
use yii\data\DataProviderInterface;

/**
 * @property string                $modelClassName; название класса модели с которой идет работа
 * @property DataProviderInterface $dataProvider; готовый датапровайдер с учетом настроек виджета
 * @property array                 $resultColumns; готовый конфиг для построения колонок
 *
 * Class ShopProductFiltersWidget
 * @package skeeks\cms\cmsWidgets\filters
 */
class GridConfig extends Component
{
    public $test;
    public $test2;

    public function rules()
    {
        return [
            ['test', 'required'],
            ['test', 'string'],
            ['test2', 'integer'],
            /*['test2', function($attribute) {
                $this->addError($attribute, 'Думайте головой');
            }],*/
        ];
    }

    public function attributeLabels()
    {
        return [
            'test' => 'Тест',
            'test2' => 'Тест2',
        ];
    }

    /**
     * @return array
     */
    public function getConfigFormFields()
    {
        return [
            'test',
            'test2',
        ];
    }

}