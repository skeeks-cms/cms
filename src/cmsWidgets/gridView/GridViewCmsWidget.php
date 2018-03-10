<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 25.05.2015
 */

namespace skeeks\cms\cmsWidgets\gridView;

use skeeks\cms\base\WidgetRenderable;
use skeeks\cms\helpers\PaginationConfig;
use skeeks\yii2\form\fields\FieldSet;
use skeeks\yii2\form\fields\SelectField;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\data\DataProviderInterface;
use yii\db\ActiveQueryInterface;
use yii\grid\GridView;
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
class GridViewCmsWidget extends WidgetRenderable
{
    const EVENT_READY = 'ready';

    /**
     * @var
     */
    public $modelClassName;

    /**
     * @var array конфиг оригинального yii2 виджета
     */
    public $gridClassName;

    /**
     * @var array
     */
    public $gridConfig = [];

    /**
     * @var array по умолчанию включенные колонки
     */
    public $visibleColumns = [];

    /**
     * @var bool генерировать колонки по названию модели автоматически
     */
    public $isEnabledAutoColumns = true;

    /**
     * @var array колонки сконфигурированные при вызове
     */
    public $columns = [];

    public $paginationConfigArray = [];
    /**
     * @var array результирующий массив конфига колонок
     */
    protected $_resultColumns = [];
    /**
     * @var PaginationConfig
     */
    protected $_paginationConfig;
    /**
     * @var array автоматически созданные колонки
     */
    protected $_autoColumns = [];
    /**
     * @var ActiveDataProvider
     */
    protected $_dataProvider = null;
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
        //Определение класса виджета yii2
        if (!$this->gridClassName) {
            $this->gridClassName = GridView::class;
        }
        //Создание датапровайдера исходя из настроек вызова виджета
        $this->dataProvider;

        //Автомтическое конфигурирование колонок
        $this->guessColumns();

        //Сбор результирующего конфига колонок
        $this->initColumns();

        //Получение настроек из хранилища
        parent::init();

        //Применение включенных/выключенных колонок
        $this->applyColumns();

        $this->paginationConfig->initDataProvider($this->dataProvider);

        //Конфигурирование настроек виджета
        $gridConfig = (array)$this->gridConfig;
        $gridConfig['columns'] = $this->_resultColumns;
        $gridConfig['dataProvider'] = $this->dataProvider;

        $this->gridConfig = $gridConfig;

        $this->trigger(self::EVENT_READY);
    }
    /**
     * This function tries to guess the columns to show from the given data
     * if [[columns]] are not explicitly specified.
     */
    protected function guessColumns()
    {
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
    public function initColumns()
    {
        $result = [];
        $autoColumns = $this->_autoColumns;
        $columns = ArrayHelper::merge(
            (array)ArrayHelper::getValue($this->gridConfig, 'columns', []),
            $this->columns
        );

        //Если автоопределение колонок не включено
        if (!$this->isEnabledAutoColumns) {
            $this->_resultColumns = $columns;
            return $this;
        }

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
        $this->_resultColumns = $columns;
        return $this;
    }
    protected function applyColumns()
    {
        $result = [];
        //Есть логика включенных выключенных колонок
        if ($this->visibleColumns && $this->_resultColumns) {

            foreach ($this->visibleColumns as $key) {

                $config = ArrayHelper::getValue($this->_resultColumns, $key);
                $config['visible'] = true;
                ArrayHelper::remove($this->_resultColumns, $key);
                $result[$key] = $config;
            }

            foreach ($this->_resultColumns as $key => $config) {
                $config['visible'] = false;
                $this->_resultColumns[$key] = $config;
            }

            $result = ArrayHelper::merge($result, $this->_resultColumns);
            $this->_resultColumns = $result;
        }

        return $this;
    }
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
    public function rules()
    {
        return [
            ['visibleColumns', 'safe'],
            ['paginationConfigArray', 'safe'],
        ];
    }
    public function attributeLabels()
    {
        return [
            'visibleColumns' => 'Включенные колонки',
        ];
    }
    public function getConfigFormModels()
    {
        return [
            'paginationConfig' => $this->paginationConfig,
        ];
    }
    /**
     * @return array
     */
    public function getConfigFormFields()
    {
        return [
            'main'             => [
                'class'  => FieldSet::class,
                'name'   => \Yii::t('skeeks/cms', 'Main'),
                'fields' => [
                    'visibleColumns' => [
                        'class'           => SelectField::class,
                        'multiple'        => true,
                        'on beforeRender' => function ($e) {
                            /**
                             * @var $selectField SelectField
                             */
                            $selectField = $e->sender;

                            if (\Yii::$app->controller && isset(\Yii::$app->controller->callableData) && \Yii::$app->controller->callableData
                                && is_array(\Yii::$app->controller->callableData)
                            ) {
                                $resultColumns = ArrayHelper::getValue(\Yii::$app->controller->callableData, 'resultColumns');
                                $selectField->items = $resultColumns;
                            }
                        },
                    ],
                ],
            ],
            'paginationConfig' => [
                'class'  => FieldSet::class,
                'name'   => \Yii::t('skeeks/cms', 'Pagination'),
                'fields' => $this->paginationConfig->getConfigFormFields(),
                'model'  => $this->paginationConfig,
            ],
        ];
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
    public function getColumnsKeyLabels()
    {
        $result = [];

        foreach ($this->_resultColumns as $code => $column) {
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
     * @return array
     */
    protected function _getRealVisibleColumns()
    {
        $result = [];

        foreach ($this->_resultColumns as $key => $column) {
            if (ArrayHelper::getValue($column, 'visible')) {
                $result[] = $key;
            }
        }

        return $result;
    }
    protected function _run()
    {
        $className = $this->gridClassName;
        echo $className::widget($this->gridConfig);
    }
}