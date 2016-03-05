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
use yii\widgets\ActiveForm;

/**
 * Class GridViewSettings
 * @package skeeks\cms\modules\admin\widgets\gridView
 */
class GridViewSettings extends Component
{
    static public function descriptorConfig()
    {
        return array_merge(parent::descriptorConfig(), [
            'name' => \Yii::t('app','Table settings')
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
            'enabledPjaxPagination'     => \Yii::t('app','Inclusion {ajax} navigation',['ajax' => 'ajax']),
            'pageParamName'             => \Yii::t('app','Parameter name pages, pagination'),
            'pageSize'                  => \Yii::t('app','Number of records on one page'),

            'orderBy'                   => \Yii::t('app','Sort by what parameter'),
            'order'                     => \Yii::t('app','sorting direction'),

            'visibleColumns'            => \Yii::t('app','Display column'),
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

    public function renderConfigForm(ActiveForm $form)
    {
        echo \Yii::$app->view->renderFile(__DIR__ . '/_form.php', [
            'form'  => $form,
            'model' => $this
        ], $this);
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