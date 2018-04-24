<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 25.05.2015
 */

namespace skeeks\cms\widgets;

use skeeks\cms\helpers\PaginationConfig;
use skeeks\yii2\config\ConfigBehavior;
use skeeks\yii2\config\ConfigTrait;
use skeeks\yii2\form\fields\FieldSet;
use skeeks\yii2\form\fields\SelectField;
use skeeks\yii2\form\fields\WidgetField;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\data\DataProviderInterface;
use yii\db\ActiveQueryInterface;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;

/**
 * @property string                $modelClassName; название класса модели с которой идет работа
 * @property DataProviderInterface $dataProvider; готовый датапровайдер с учетом настроек виджета
 * @property array                 $resultColumns; готовый конфиг для построения колонок
 * @property PaginationConfig      $paginationConfig;
 *
 * Class ShopProductFiltersWidget
 * @package skeeks\cms\cmsWidgets\filters
 */
class GridView extends \yii\grid\GridView
{
    use ConfigTrait;

    /**
     * @var
     */
    public $modelClassName;

    /**
     * @var array по умолчанию включенные колонки
     */
    public $visibleColumns = [];

    /**
     * @var bool генерировать колонки по названию модели автоматически
     */
    public $isEnabledAutoColumns = true;

    /**
     * @var array
     */
    public $paginationConfigArray = [];

    /**
     * @var array результирующий массив конфига колонок
     */
    protected $_preInitColumns = [];
    /**
     * @var PaginationConfig
     */
    protected $_paginationConfig;
    /**
     * @var array автоматически созданные колонки
     */
    protected $_autoColumns = [];

    /**
     * @var array
     */
    public $configBehaviorData = [];

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            ConfigBehavior::class => ArrayHelper::merge([
                'class'       => ConfigBehavior::class,
                'configModel' => [
                    'fields'           => [
                        'main'             => [
                            'class'  => FieldSet::class,
                            'name'   => \Yii::t('skeeks/cms', 'Main'),
                            'fields' => [
                                'caption',
                                'visibleColumns' => [
                                    'class'           => WidgetField::class,
                                    'widgetClass'     => DualSelect::class,
                                    'widgetConfig'    => [
                                        'visibleLabel' => \Yii::t('skeeks/cms', 'Display columns'),
                                        'hiddenLabel' => \Yii::t('skeeks/cms', 'Hidden columns'),
                                    ],
                                    //'multiple'        => true,
                                    'on beforeRender' => function ($e) {
                                        /**
                                         * @var $gridView GridView
                                         */
                                        //$gridView = $e->sender->model->configBehavior->owner;
                                        /**
                                         * @var $widgetField WidgetField
                                         */
                                        $widgetField = $e->sender;
                                        $widgetField->widgetConfig['items'] = ArrayHelper::getValue(
                                            \Yii::$app->controller->getCallableData(),
                                            'availableColumns'
                                        );
                                    },
                                ],
                            ],
                        ],
                        /*'paginationConfig' => [
                            'class'  => FieldSet::class,
                            'name'   => \Yii::t('skeeks/cms', 'Pagination'),
                            'fields' => $this->paginationConfig->builderFields(),
                            'model'  => $this->paginationConfig,
                        ],*/
                    ],
                    'attributeDefines' => [
                        'visibleColumns',
                        'caption',
                    ],
                    'attributeLabels'  => [
                        'visibleColumns' => 'Отображаемые колонки',
                        'caption' => 'Заголовок таблицы',
                    ],
                    'rules'            => [
                        ['visibleColumns', 'required'],
                        ['visibleColumns', 'safe'],
                        ['caption', 'string'],
                    ],
                ],
            ], (array) $this->configBehaviorData),
        ]);
    }

    public function getColumnsKeyLabels()
    {
        $result = [];

        foreach ($this->_preInitColumns as $code => $column) {
            $attribute = '';
            $label = '';

            if (is_array($column)) {
                if (ArrayHelper::getValue($column, 'label')) {
                    if (ArrayHelper::getValue($column, 'label') !== false) {
                        $label = ArrayHelper::getValue($column, 'label');
                    }
                } elseif (ArrayHelper::getValue($column, 'attribute')) {
                    $attribute = ArrayHelper::getValue($column, 'attribute');
                }
            } else {
                $attribute = $code;
            }

            if ($label) {
                $result[$code] = $label;
            } elseif ($attribute) {

                $provider = $this->dataProvider;

                if ($provider instanceof ActiveDataProvider && $provider->query instanceof ActiveQueryInterface) {
                    /* @var $model Model */
                    $model = new $provider->query->modelClass;
                    $label = $model->getAttributeLabel($attribute);
                } else {
                    $models = $provider->getModels();
                    if (($model = reset($models)) instanceof Model) {
                        /* @var $model Model */
                        $label = $model->getAttributeLabel($attribute);
                    } else {
                        $label = Inflector::camel2words($attribute);
                    }
                }

                if ($result && in_array($label, array_values($result))) {
                    $result[$code] = $label." ({$code})";
                } else {
                    $result[$code] = $label;
                }

            } else {
                $result[$code] = Inflector::camel2words($code);
            }
        }

        return $result;
    }
    /**
     * @return PaginationConfig
     */
    public function getPaginationConfig()
    {
        if ($this->_paginationConfig === null) {
            $this->_paginationConfig = new PaginationConfig();
            $this->_paginationConfig->setAttributes($this->paginationConfigArray);
        }

        return $this->_paginationConfig;
    }
    /**
     *
     */
    public function init()
    {
        //Создание датапровайдера исходя из настроек вызова виджета
        if (!$this->dataProvider) {
            $this->dataProvider = $this->_createDataProvider();
        }
        //Автомтическое конфигурирование колонок
        $this->_initAutoColumns();

        //Сбор результирующего конфига колонок
        $this->_preInitColumns();
        //Получение настроек из хранилища


        parent::init();


        //Применение включенных/выключенных колонок
        $this->applyColumns();

        $this->paginationConfig->initDataProvider($this->dataProvider);
    }
    /**
     * @return ActiveDataProvider
     */
    protected function _createDataProvider()
    {
        $modelClassName = $this->modelClassName;

        if ($modelClassName) {
            return new ActiveDataProvider([
                'query' => $modelClassName::find(),
            ]);
        } else {
            return new ArrayDataProvider([
                'allModels' => [],
            ]);
        }

    }
    /**
     * This function tries to guess the columns to show from the given data
     * if [[columns]] are not explicitly specified.
     */
    protected function _initAutoColumns()
    {

        //Если автоопределение колонок не включено
        if (!$this->isEnabledAutoColumns) {
            return $this;
        }

        $dataProvider = clone $this->dataProvider;
        $models = $dataProvider->getModels();

        $model = reset($models);
        if (is_array($model) || is_object($model)) {
            foreach ($model as $name => $value) {
                if ($value === null || is_scalar($value) || is_callable([$value, '__toString'])) {
                    $this->_autoColumns[(string)$name] = [
                        'attribute' => $name,
                        'format'    => 'raw',
                        'value'     => function ($model, $key, $index) use ($name) {
                            if (is_array($model->{$name})) {
                                return implode(",", $model->{$name});
                            } else {
                                return $model->{$name};
                            }
                        },
                    ];
                }
            }
        }

        return $this;
    }
    /**
     * @return array
     */
    protected function _preInitColumns()
    {
        $result = [];
        $autoColumns = $this->_autoColumns;
        $columns = $this->columns;

        if ($columns) {
            foreach ($columns as $key => $value) {
                //Если с таким ключем есть автоколонка, нужно убрать ее из авто
                if (is_string($key)) {
                    ArrayHelper::removeValue($autoColumns, $key);
                }

                if (is_string($value)) {
                    ArrayHelper::removeValue($autoColumns, $value);
                    $columns[$key] = [
                        'attribute' => $value,
                    ];
                }

                if (is_array($value)) {
                    if ($attribute = ArrayHelper::getValue($value, 'attibute')) {
                        ArrayHelper::removeValue($autoColumns, $attribute);
                    }
                }
            }
        }

        $columns = ArrayHelper::merge((array)$autoColumns, (array)$columns);

        foreach ($columns as $key => $config) {
            $config['visible'] = true;
            $columns[$key] = $config;
        }

        $this->_preInitColumns = $columns;
        $this->columns = $this->_preInitColumns;

        return $this;
    }
    protected function applyColumns()
    {
        $result = [];
        //Есть логика включенных выключенных колонок
        if ($this->visibleColumns && $this->columns) {

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
    /**
     * @return array
     */
    public function getCallableData()
    {
        $result = parent::getCallableData();

        $result['resultColumns'] = $this->getColumnsKeyLabels();
        $result['visibleColumns'] = $this->_getRealVisibleColumns();

        return $result;
    }
    /**
     * @return array
     */
    protected function _getRealVisibleColumns()
    {
        $result = [];

        foreach ($this->_preInitColumns as $key => $column) {
            if (ArrayHelper::getValue($column, 'visible')) {
                $result[] = $key;
            }
        }

        return $result;
    }


    /**
     * Данные необходимые для редактирования компонента, при открытии нового окна
     * @return array
     */
    public function getEditData()
    {
        return [
            'callAttributes' => $this->callAttributes,
            'availableColumns' => $this->getColumnsKeyLabels(),
        ];
    }
}