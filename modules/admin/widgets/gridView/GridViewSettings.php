<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 02.06.2015
 */
namespace skeeks\cms\modules\admin\widgets\gridView;
use skeeks\cms\base\Component;
use skeeks\cms\components\Cms;
use skeeks\cms\modules\admin\widgets\GridViewHasSettings;
use yii\helpers\ArrayHelper;

/**
 * Class GridViewSettings
 * @package skeeks\cms\modules\admin\widgets\gridView
 */
class GridViewSettings extends Component
{
    static public function descriptorConfig()
    {
        return array_merge(parent::descriptorConfig(), [
            'name' => 'Настройки таблицы'
        ]);
    }

    public $enabledPjaxPagination;
    /**
     * @var int
     */
    public $pageSize;
    /**
     * @var string
     */
    public $pageParamName;


    /**
     * @var array
     */
    public $visibleColumns = [];


    /**
     * @var GridViewHasSettings
     */
    public $grid;

    //Сортировка
    public $orderBy                     = "id";
    public $order                       = SORT_DESC;

    public function init()
    {
        $this->pageSize                 = \Yii::$app->admin->pageSize;
        $this->pageParamName            = \Yii::$app->admin->pageParamName;
        $this->enabledPjaxPagination    = \Yii::$app->admin->enabledPjaxPagination;

        parent::init();
    }

    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(),
        [
            'enabledPjaxPagination'     => 'Включение ajax навигации',
            'pageParamName'             => 'Названия парамтера страниц, при постраничной навигации',
            'pageSize'                  => 'Количество записей на одной странице',

            'orderBy'                   => 'По какому параметру сортировать',
            'order'                     => 'Направление сортировки',

            'visibleColumns'            => 'Отображаемые колонки',
        ]);
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(),
        [
            [['enabledPjaxPagination'], 'string'],
            [['pageParamName'], 'string'],
            [['pageSize'], 'string'],
            [['orderBy'], 'string'],
            [['order'], 'integer'],
            [['visibleColumns'], 'safe'],
        ]);
    }

    /**
     * @return $this
     */
    public function getEditUrl()
    {
        $url = parent::getEditUrl();

        if ($this->grid)
        {
            $columnsData = $this->grid->getColumnsKeyLabels();
            $url->setSystemParam('columns', $columnsData);
            $url->setSystemParam('selectedColumns', array_keys($this->grid->columns));
        }

        return $url;
    }
}