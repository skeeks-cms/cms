<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 25.05.2015
 */

namespace skeeks\cms\widgets;

use skeeks\cms\helpers\PaginationConfig;
use skeeks\cms\IHasModel;
use skeeks\yii2\config\ConfigBehavior;
use skeeks\yii2\config\DynamicConfigModel;
use skeeks\yii2\form\fields\SelectField;
use yii\base\Widget;
use yii\data\ActiveDataProvider;
use yii\data\DataProviderInterface;
use yii\helpers\ArrayHelper;

/**
 * @property string                $modelClassName; название класса модели с которой идет работа
 * @property DataProviderInterface $dataProvider; готовый датапровайдер с учетом настроек виджета
 * @property array                 $resultColumns; готовый конфиг для построения колонок
 * @property PaginationConfig      $paginationConfig;
 *
 * Class ShopProductFiltersWidget
 * @package skeeks\cms\cmsWidgets\filters
 */
class FiltersWidget extends Widget
{
    /**
     * @var ActiveDataProvider
     */
    public $dataProvider;

    /**
     * @var array по умолчанию включенные колонки
     */
    public $visibleFilters = [];

    /**
     * @var array
     */
    public $config = [];

    /**
     * @var IHasModel|array|DynamicConfigModel
     */
    public $filtersModel;


    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            ConfigBehavior::class => ArrayHelper::merge([
                'class'       => ConfigBehavior::class,
                'configModel' => [
                    'fields'           => [
                        'visibleFilters' => [
                            'class'           => SelectField::class,
                            'multiple'        => true,
                            'on beforeRender' => function ($e) {
                                /**
                                 * @var $gridView FiltersWidget
                                 */
                                $gridView = $e->sender->model->configBehavior->owner;
                                /**
                                 * @var $selectField SelectField
                                 */
                                $selectField = $e->sender;
                                $selectField->items = $gridView->getColumnsKeyLabels();
                            },
                        ],
                    ],
                    'attributeDefines' => [
                        'visibleFilters',
                    ],
                    'attributeLabels'  => [
                        'visibleFilters' => 'Отображаемые фильтры',
                    ],
                    'rules'            => [
                        ['visibleFilters', 'safe'],
                    ],
                ],
            ],
                (array)$this->config),
        ]);
    }

    /**
     *
     */
    public function init()
    {
        $defaultFiltersModel = [
           'class' => DynamicConfigModel::class,
        ];

        $this->filtersModel = ArrayHelper::merge($defaultFiltersModel, (array) $this->filtersModel);
        $this->filtersModel = \Yii::createObject($this->filtersModel);
        $this->filtersModel->load(\Yii::$app->request->get());

        if ($this->filtersModel->builderFields()) {
            foreach ($this->filtersModel->builderFields() as $key => $field)
            {

            }
        }

        $this->trigger(self::EVENT_INIT);
    }

    public function run()
    {
        $form = \yii\bootstrap\ActiveForm::begin([
            'action' => [''],
            'method' => 'get',
            'layout' => 'horizontal',
        ]);


        echo (new \skeeks\yii2\form\Builder([
            'models'     => $this->filtersModel->builderModels(),
            'model'      => $this->filtersModel,
            'activeForm' => $form,
            'fields'     => $this->filtersModel->builderFields(),
        ]))->render();


        \yii\bootstrap\ActiveForm::end();
    }


    protected function applyColumns()
    {
        $result = [];
        //Есть логика включенных выключенных колонок
        if ($this->visibleFilters && $this->columns) {

            foreach ($this->visibleColumns as $key) {
                $result[$key] = ArrayHelper::getValue($this->columns, $key);
            }

            /*foreach ($this->_resultColumns as $key => $config) {
                $config['visible'] = false;
                $this->_resultColumns[$key] = $config;
            }*/

            /*$result = ArrayHelper::merge($result, $this->_resultColumns);
            $this->_resultColumns = $result;*/
            $this->columns = $result;
        }

        return $this;
    }


}