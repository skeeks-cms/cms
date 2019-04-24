<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 25.05.2015
 */

namespace skeeks\cms\widgets;

use skeeks\cms\backend\helpers\BackendUrlHelper;
use skeeks\cms\base\ActiveRecord;
use skeeks\cms\helpers\PaginationConfig;
use skeeks\cms\widgets\assets\GridViewAsset;
use skeeks\yii2\config\ConfigBehavior;
use skeeks\yii2\config\ConfigTrait;
use skeeks\yii2\form\fields\FieldSet;
use skeeks\yii2\form\fields\WidgetField;
use yii\base\Component;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\data\DataProviderInterface;
use yii\db\ActiveQuery;
use yii\db\ActiveQueryInterface;
use yii\grid\DataColumn;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;

/**
 * @property string                $modelClassName; название класса модели с которой идет работа
 * @property DataProviderInterface|ActiveDataProvider $dataProvider; готовый датапровайдер с учетом настроек виджета
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
     * @var array результирующий массив конфига колонок
     */
    protected $_preInitColumns = [];
    /**
     * @var array автоматически созданные колонки
     */
    protected $_autoColumns = [];

    /**
     * @var array
     */
    public $configBehaviorData = [];


    /**
     * @var string name of the parameter storing the current page index.
     * @see params
     */
    public $pageParam = 'page';
    /**
     * @var string name of the parameter storing the page size.
     * @see params
     */
    public $pageSizeParam = 'per-page';

    /**
     * @var int the default page size. This property will be returned by [[pageSize]] when page size
     * cannot be determined by [[pageSizeParam]] from [[params]].
     */
    public $defaultPageSize = 20;
    /**
     * @var array|false the page size limits. The first array element stands for the minimal page size, and the second
     * the maximal page size. If this is false, it means [[pageSize]] should always return the value of [[defaultPageSize]].
     */
    public $pageSizeLimitMin = 1;

    /**
     * @var int
     */
    public $pageSizeLimitMax = 50;

    /**
     * @var array
     */
    public $defaultOrder = [];
    /**
     * @var array
     */
    public $sortAttributes = [];


    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            ConfigBehavior::class => ArrayHelper::merge([
                'class'       => ConfigBehavior::class,
                'configModel' => [
                    /*'on load' => function(Event $event) {
                        print_r($event->data);die;
                        $this->paginationConfig->load($event->data);
                        $this->paginationConfigArray = $this->paginationConfig->toArray();
                    },*/
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
                                        'hiddenLabel'  => \Yii::t('skeeks/cms', 'Hidden columns'),
                                    ],
                                    //'multiple'        => true,
                                    'on beforeRender' => function ($e) {
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
                        /*'sort' => [
                            'class'  => FieldSet::class,
                            'name'   => \Yii::t('skeeks/cms', 'Sorting'),
                            'fields' => [
                                'defaultOrder'  => [
                                    'class'           => WidgetField::class,
                                    'widgetClass'     => SortSelect::class,
                                    'on beforeRender' => function ($e) {
                                                                                /**
                                         * @var $widgetField WidgetField
                                        $widgetField = $e->sender;
                                        $widgetField->widgetConfig['items'] = ArrayHelper::getValue(
                                            \Yii::$app->controller->getCallableData(),
                                            'sortAttributes'
                                        );

                                    },
                                ],
                            ],
                        ],*/
                        'paginationConfig' => [
                            'class'  => FieldSet::class,
                            'name'   => \Yii::t('skeeks/cms', 'Pagination'),
                            'fields' => [
                                'defaultPageSize'  => [
                                    'elementOptions' => [
                                        'type' => 'number',
                                    ],
                                ],
                                'pageSizeLimitMin' => [
                                    'elementOptions' => [
                                        'type' => 'number',
                                    ],
                                ],
                                'pageSizeLimitMax' => [
                                    'elementOptions' => [
                                        'type' => 'number',
                                    ],
                                ],
                                'pageParam',
                                'pageSizeParam',
                            ],
                        ],
                    ],
                    'attributeDefines' => [
                        'visibleColumns',
                        'caption',

                        'pageParam',
                        'defaultPageSize',
                        'pageSizeLimitMin',
                        'pageSizeLimitMax',
                        'pageSizeParam',

                        'defaultOrder',
                    ],
                    'attributeLabels'  => [
                        'visibleColumns' => 'Отображаемые колонки',
                        'caption'        => 'Заголовок таблицы',

                        'pageParam'        => \Yii::t('skeeks/cms', 'Parameter name pages, pagination'),
                        'defaultPageSize'  => \Yii::t('skeeks/cms', 'Number of records on one page'),
                        'pageSizeLimitMin' => \Yii::t('skeeks/cms', 'The minimum allowable value for pagination'),
                        'pageSizeLimitMax' => \Yii::t('skeeks/cms', 'The maximum allowable value for pagination'),
                        'pageSizeParam'    => \Yii::t('skeeks/cms', 'pageSizeParam'),

                        'defaultOrder' => 'Сортировка',
                    ],
                    'rules'            => [
                        ['visibleColumns', 'required'],
                        ['visibleColumns', 'safe'],
                        ['defaultOrder', 'safe'],
                        ['caption', 'string'],

                        [['pageParam', 'pageSizeParam', 'defaultPageSize'], 'required'],
                        [['pageParam', 'pageSizeParam'], 'string'],
                        ['defaultPageSize', 'integer'],
                        ['pageSizeLimitMin', 'integer'],
                        ['pageSizeLimitMax', 'integer'],
                    ],
                ],
            ], (array)$this->configBehaviorData),
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
    /*public function getPaginationConfig()
    {
        if ($this->_paginationConfig === null) {
            $this->_paginationConfig = new PaginationConfig();
            $this->_paginationConfig->setAttributes($this->paginationConfigArray);
        }

        return $this->_paginationConfig;
    }*/


    /**
     *
     */
    public function init()
    {
        //Создание датапровайдера исходя из настроек вызова виджета
        if (!$this->dataProvider) {
            $this->dataProvider = $this->_createDataProvider();
        }

        if (is_callable($this->dataProvider)) {
            $callable = $this->dataProvider;
            $this->dataProvider = call_user_func($callable, $this);
        }
        //Автомтическое конфигурирование колонок
        $this->_initAutoColumns();

        //Сбор результирующего конфига колонок
        $this->_preInitColumns();

        $this->_initDialogCallbackData();
        //Получение настроек из хранилища
        parent::init();

        //Применение включенных/выключенных колонок
        $this->_applyColumns();

        $this->_initPagination();
        $this->_initSort();

        //Если удалили колонки
        foreach ($this->columns as $key => $column) {
            if (!is_object($column)) {
                unset($this->columns[$key]);
            }
        }
    }

    public $exportParam = '_sx-export';
    public $exportFileName = 'export';

    public function run()
    {
        if (\Yii::$app->request->get($this->exportParam) == $this->id) {

            ob_clean();

            $out = fopen('php://output', 'w');

            foreach ($this->columns as $column) {
                /* @var $column DataColumn */
                $cells[] = iconv("UTF-8", "windows-1251", strip_tags($column->renderHeaderCell()));
            }

            fputcsv($out, $cells, ";");

            if (isset($this->dataProvider->query)) {
                foreach ($this->dataProvider->query->each(10) as $key => $model) {
                    $cells = [];

                    foreach ($this->columns as $column) {
                        $cells[] = iconv("UTF-8", "windows-1251//IGNORE", strip_tags($column->renderDataCell($model, $key, $key)));
                    }

                    fputcsv($out, $cells, ";");
                }
            }


            fclose($out);
            $filename = $this->exportFileName . ".csv";
            header('Content-Type: text/csv');

            // disable caching
            $now = gmdate("D, d M Y H:i:s");
            header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
            header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
            header("Last-Modified: {$now} GMT");

            // force download
            header("Content-Type: application/force-download");
            header("Content-Type: application/octet-stream");
            header("Content-Type: application/download");

            header("Content-Transfer-Encoding: binary");
            header('Content-Disposition: attachment;filename='.$filename);

            \Yii::$app->end();
        }


        GridViewAsset::register($this->view);
        return parent::run();
    }

    protected function _initDialogCallbackData()
    {

        if ($callbackEventName = BackendUrlHelper::createByParams()->setBackendParamsByCurrentRequest()->callbackEventName) {
            $this->view->registerJs(<<<JS
(function(sx, $, _)
{
    sx.classes.SelectCmsElement = sx.classes.Component.extend({

        _onDomReady: function()
        {
            $('table tr').on('dblclick', function()
            {
                $(".sx-row-action", $(this)).click();
            });
        },

        submit: function(data)
        {
            if (window.opener)
            {
                if (window.opener.sx)
                {
                    window.opener.sx.EventManager.trigger('{$callbackEventName}', data);
                    return this;
                }
            } else if (window.parent)
            {
                if (window.parent.sx)
                {
                    window.parent.sx.EventManager.trigger('{$callbackEventName}', data);
                    return this;
                }
            }

            return this;
        }
    });

    sx.SelectCmsElement = new sx.classes.SelectCmsElement();

})(sx, sx.$, sx._);
JS
            );

            $this->columns = ArrayHelper::merge([

                'sx-choose' => $this->getDialogCallbackDataColumn(),

            ], $this->columns);

            if ($this->visibleColumns) {
                $this->visibleColumns = ArrayHelper::merge(['sx-choose'], $this->visibleColumns);
            }
        }
    }


    /**
     * @param DataProviderInterface $dataProvider
     * @return $this
     */
    protected function _initPagination()
    {
        $dataProvider = $this->dataProvider;

        $dataProvider->getPagination()->defaultPageSize = $this->defaultPageSize;
        $dataProvider->getPagination()->pageParam = $this->pageParam;
        $dataProvider->getPagination()->pageSizeParam = $this->pageSizeParam;
        $dataProvider->getPagination()->pageSizeLimit = [
            (int)$this->pageSizeLimitMin,
            (int)$this->pageSizeLimitMax,
        ];

        return $this;
    }

    /**
     * @param DataProviderInterface $dataProvider
     * @return $this
     */
    protected function _initSort()
    {
        $dataProvider = $this->dataProvider;

        $dataProvider->getSort()->attributes = ArrayHelper::merge($dataProvider->getSort()->attributes, $this->sortAttributes);

        //Бывает ситуация когда сохранили настройки сортировки а потом удалил поля и забыз и как таковых атрибутов для сортировки уже нет
        if ($this->defaultOrder && is_array($this->defaultOrder)) {
            foreach ($this->defaultOrder as $key => $value)
            {
                if (!isset($dataProvider->getSort()->attributes[$key])) {
                    unset($this->defaultOrder[$key]);
                }
            }
        }

        $dataProvider->getSort()->defaultOrder = $this->defaultOrder;

        return $this;
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

        /**
         * @var $model ActiveQuery
         */
        $model = reset($models);

        if (!$model) {
            if ($dataProvider && isset($dataProvider->query) && $dataProvider->query->modelClass) {
                $modelClass = $dataProvider->query->modelClass;
                $model = new $modelClass();
            }
        }


        if (is_array($model) || is_object($model)) {
            foreach ($model as $name => $value) {
                if ($value === null || is_scalar($value) || is_callable([$value, '__toString'])) {

                    $key = $name;
                    if (!empty($key) && strcasecmp($key, 'id')) {
                        if (substr_compare($key, 'id', -2, 2, true) === 0) {
                            $key = rtrim(substr($key, 0, -2), '_');
                        } elseif (substr_compare($key, 'id', 0, 2, true) === 0) {
                            $key = ltrim(substr($key, 2, strlen($key)), '_');
                        }
                    }

                    $keyMany = Inflector::pluralize($key);

                    $keyName = lcfirst(Inflector::id2camel($key, '_'));
                    $keyManyName = lcfirst(Inflector::id2camel($keyMany, '_'));

                    if ($model instanceof Component && $model->hasProperty($keyName)) {
                        $this->_autoColumns[(string)$name] = [
                            'attribute' => $name,
                            'format'    => 'raw',
                            'value'     => function ($model, $key, $index) use ($name, $keyName) {
                                return $model->{$keyName};
                            },
                        ];
                    } else if ($model instanceof Component && $model->hasProperty(lcfirst($keyManyName))) {
                        $this->_autoColumns[(string)$name] = [
                            'attribute' => $name,
                            'format'    => 'raw',
                            'value'     => function ($model, $key, $index) use ($name, $keyManyName) {
                                return count($model->{$keyManyName});
                            },
                        ];
                    } else {
                        $this->_autoColumns[(string)$name] = [
                            'attribute' => $name,
                            'format'    => 'raw',
                            'value'     => function ($model, $key, $index) use ($name) {
                                if (is_array($model)) {
                                    $v = $model[$name];
                                } else {
                                    $v = $model->{$name};
                                }

                                if (is_array($v)) {
                                    return implode(",", $v);
                                } else {
                                    return $v;
                                }
                            },
                        ];
                    }

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

        $columnsTmp = (array)$columns;
        $columns = ArrayHelper::merge((array)$autoColumns, (array)$columns);

        foreach ($columns as $key => $config) {
            $config['visible'] = true;
            $columns[$key] = $config;
        }

        $resultColumns = [];

        if ($columnsTmp) {
            foreach ($columnsTmp as $key => $column) {
                if (isset($columns[$key])) {
                    $resultColumns[$key] = $columns[$key];
                    unset($columns[$key]);
                }
            }
        }

        if ($resultColumns) {
            $resultColumns = ArrayHelper::merge((array)$resultColumns, (array)$columns);
            $columns = $resultColumns;
        }

        $this->_preInitColumns = $columns;
        $this->columns = $this->_preInitColumns;

        return $this;
    }

    protected function _applyColumns()
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
        $sort = [];
        if ($this->dataProvider->getSort()->attributes) {
            foreach ($this->dataProvider->getSort()->attributes as $key => $value) {
                $sort[$key] = ArrayHelper::getValue($value, 'label');
            }
        }

        return [
            'callAttributes'   => $this->callAttributes,
            'availableColumns' => $this->getColumnsKeyLabels(),
            'sortAttributes'   => $sort,
        ];
    }


    protected $_dialogCallbackDataColumn = null;

    /**
     * @var null
     */
    public $dialogCallbackData = null;

    public function setDialogCallbackDataColumn($column)
    {
        $this->_dialogCallbackDataColumn = $column;
        return $this;
    }

    public function getDialogCallbackDataColumn()
    {
        if ($this->_dialogCallbackDataColumn === null) {
            $this->_dialogCallbackDataColumn = [
                'class'  => \yii\grid\DataColumn::className(),
                'value'  => function ($model) {
                    $data = $model->toArray();

                    if ($this->dialogCallbackData && is_callable($this->dialogCallbackData)) {
                        $callback = $this->dialogCallbackData;
                        $data = $callback($model);
                    } else {
                        if ($model instanceof ActiveRecord) {
                            $data = ArrayHelper::merge($model->toArray(), [
                                'asText' => $model->asText,
                            ]);
                        }

                    }

                    return \yii\helpers\Html::a('<i class="glyphicon glyphicon-circle-arrow-left"></i> '.\Yii::t('skeeks/cms',
                            'Choose'), '#', [
                        'class'     => 'btn btn-primary sx-row-action',
                        'onclick'   => 'sx.SelectCmsElement.submit('.\yii\helpers\Json::encode($data).'); return false;',
                        'data-pjax' => 0,
                    ]);
                },
                'label'  => '',
                'format' => 'raw',
            ];
        }
        return $this->_dialogCallbackDataColumn;
    }

}