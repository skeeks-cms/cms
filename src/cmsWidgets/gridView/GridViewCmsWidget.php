<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 25.05.2015
 */

namespace skeeks\cms\cmsWidgets\gridView;

use skeeks\cms\base\WidgetRenderable;
use skeeks\yii2\form\fields\FieldSet;
use yii\base\DynamicModel;
use yii\data\ActiveDataProvider;
use yii\data\DataProviderInterface;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;

/**
 * @property string                $modelClassName; название класса модели с которой идет работа
 * @property DataProviderInterface $dataProvider; готовый датапровайдер с учетом настроек виджета
 * @property array                 $resultColumns; готовый конфиг для построения колонок
 *
 * Class ShopProductFiltersWidget
 * @package skeeks\cms\cmsWidgets\filters
 */
class GridViewCmsWidget extends WidgetRenderable
{
    /**
     * @var array конфиг оригинального yii2 виджета
     */
    public $grid = [];

    /**
     * @var array по умолчанию включенные колонки
     */
    public $defaultEnabledColumns = [];

    /**
     * @var bool генерировать колонки по названию модели автоматически
     */
    public $isEnabledAutoColumns = true;

    /**
     * @var array
     */
    public $columns = [];





    public $test = '1';

    public $gridConfigArray = [];
    /**
     * @var GridConfig
     */
    public $gridConfig;

    public function init()
    {
        parent::init();

        $this->gridConfig = new GridConfig();
        $this->gridConfig->setAttributes($this->gridConfigArray);
    }

    public function rules()
    {
        return [
            ['test', 'string'],
            ['gridConfigArray', 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'test' => 'Тест',
        ];
    }

    public function getConfigFormModels()
    {
        return [
            'gridConfig' => $this->gridConfig
        ];
    }

    /**
     * @return array
     */
    public function getConfigFormFields()
    {
        return [
            'main' => [
                'class'  => FieldSet::class,
                'name'   => \Yii::t('skeeks/cms', 'Main'),
                'fields' => [
                    'test',
                ],
            ],
            'gridConfig' => [
                'class'  => FieldSet::class,
                'name'   => \Yii::t('skeeks/cms', 'Grid config'),
                'fields' => $this->gridConfig->getConfigFormFields(),
                'model' => $this->gridConfig,
            ],
        ];
    }

    /**
     * @return array
     */
    public function getResultColumns()
    {
        $result = [];
        $autoColumns = $this->_autoColumns;

        //Если автоопределение колонок не включено
        if (!$this->isEnabledAutoColumns) {
            return (array) ArrayHelper::getValue($this->grid, 'columns', []);
        }

        if ($columns = ArrayHelper::getValue($this->grid, 'columns')) {
            foreach ($columns as $key => $value) {
                //Если с таким ключем есть автоколонка, нужно убрать ее из авто
                if (is_string($key)) {
                    ArrayHelper::removeValue($autoColumns, $key);
                }

                if (is_string($value)) {
                    ArrayHelper::removeValue($autoColumns, $value);
                }

                if (is_array($value)) {
                    if ($attribute = ArrayHelper::getValue($value, 'attibute')) {
                        ArrayHelper::removeValue($autoColumns, $attribute);
                    }
                }
            }
        }

        $columns = ArrayHelper::merge((array) $autoColumns, (array)$columns);

        //Есть логика включенных выключенных колонок
        if ($this->defaultEnabledColumns) {
            foreach ($columns as $key => $config)
            {
                if (in_array($key, $this->defaultEnabledColumns)) {
                    $config['visible'] = true;
                } else {
                    $config['visible'] = false;
                }

                $columns[$key] = $config;
            }
        }

        return $columns;
    }

    /**
     * @var array автоматически созданные колонки
     */
    protected $_autoColumns = [];


    protected function _run()
    {
        $this->guessColumns();
        $className = $this->getGridClassName();
        echo $className::widget($this->getGridConfig());
    }

    /**
     * @return string
     */
    public function getGridClassName()
    {
        return (string)ArrayHelper::getValue($this->grid, 'class', GridView::class);
    }

    /**
     * @return []
     */
    public function getGridConfig()
    {
        $gridConfig = (array) $this->grid;
        ArrayHelper::remove($this->grid, 'class');

        //print_r($this->columns);die;
        $gridConfig['columns'] = $this->columns;
        $gridConfig['dataProvider'] = $this->dataProvider;

        return (array)$gridConfig;
    }

    /**
     * This function tries to guess the columns to show from the given data
     * if [[columns]] are not explicitly specified.
     */
    protected function guessColumns()
    {
        $models = $this->dataProvider->getModels();

        $model = reset($models);
        if (is_array($model) || is_object($model)) {
            foreach ($model as $name => $value) {
                if ($value === null || is_scalar($value) || is_callable([$value, '__toString'])) {
                    $this->_autoColumns[(string)$name] = [
                        'attribute' => (string)$name
                    ];
                }
            }
        }

        return $this;
    }


    /**
     * @var null|string
     */
    public $_modelClassName = null;

    /**
     * @return \skeeks\cms\backend\controllers\sting
     */
    public function getModelClassName()
    {
        if ($this->_modelClassName === null) {
            $this->_modelClassName = $this->controller->modelClassName;
        }

        return $this->_modelClassName;
    }

    /**
     * @param $className
     * @return $this
     */
    public function setModelClassName($className)
    {
        $this->_modelClassName = $className;

        return $this;
    }


    /**
     * @var ActiveDataProvider
     */
    protected $_dataProvider = null;

    /**
     * @return DataProviderInterface
     */
    public function getDataProvider()
    {
        if ($this->_dataProvider === null) {
            $this->_dataProvider = $this->_createDataProvider();
        }

        return $this->_dataProvider;
    }

    /**
     * @return ActiveDataProvider
     */
    protected function _createDataProvider()
    {
        $modelClassName = $this->modelClassName;
        return new ActiveDataProvider([
            'query' => $modelClassName::find(),
        ]);
    }


}