<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 12.03.2015
 */
namespace skeeks\cms\modules\admin\widgets;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use yii\base\Widget;
use yii\helpers\ArrayHelper;

/**
 * Class RelatedModelsGrid
 * @package skeeks\cms\modules\admin\widgets
 */
class RelatedModelsGrid extends Widget
{
    /**
     * @var null контроллер который управляет связанными моделями
     */
    public $controllerRoute         = null;//'cms/admin-user-email';

    /**
     * @var string действие добавления связанной модели
     */
    public $controllerCreateAction  = 'create';
    public $controllerSortableAction  = 'sortable-priority';

    /**
     * @var string название
     */
    public $label  = '';

    /**
     * @var string небольшое описание
     */
    public $hint    = '';

    /**
     * @var array опции для грида
     */
    public $gridViewOptions  = [];

    /**
     * @var array связь
     */
    public $relation  = [];

    /**
     * @var array
     */
    public $sort = [];

    /**
     * @var Родительская модель к которой будут строиться привязанные сущьности
     */
    public $parentModel  = null;


    public function run()
    {
        if ($this->parentModel->isNewRecord)
        {
            return "";
        }

        /**
         * @var $controller AdminModelEditorController
         */
        $controller = \Yii::$app->createController($this->controllerRoute)[0];

        $rerlation = [];
        foreach ($this->relation as $relationLink => $parent)
        {
            $rerlation[$relationLink] = $this->parentModel->{$parent};
        }

        $createUrl = \skeeks\cms\helpers\UrlHelper::construct($this->controllerRoute . '/' . $this->controllerCreateAction, $rerlation)
                ->setSystemParam(\skeeks\cms\modules\admin\Module::SYSTEM_QUERY_EMPTY_LAYOUT, 'true')
                ->setSystemParam(\skeeks\cms\modules\admin\Module::SYSTEM_QUERY_NO_ACTIONS_MODEL, 'true')
                ->enableAdmin()->toString();


        $sortableUrl = \skeeks\cms\helpers\UrlHelper::construct($this->controllerRoute . '/' . $this->controllerSortableAction)
                ->enableAdmin()->toString();


        $search = new \skeeks\cms\models\Search($controller->modelClassName);
        $search->getDataProvider()->query->where($rerlation);

        if (isset($this->sort['defaultOrder']))
        {
            $search->getDataProvider()->sort->defaultOrder = $this->sort['defaultOrder'];
        }

        $pjaxId = "sx-table-" . md5($this->label . $this->hint . $this->parentModel->className());
        $gridOptions = ArrayHelper::merge([
            /*'filterModel'   => $search,*/
            'pjaxOptions' => [
                'id' => $pjaxId
            ],
            "usePjax" => true,
            "sortableOptions" => [
                'backend' => $sortableUrl
            ],
            'dataProvider'  => $search->getDataProvider(),
            'layout' => "\n{items}\n{pager}",
            'columns' => [
                //['class' => 'yii\grid\SerialColumn'],

                [
                    'class'                 => \skeeks\cms\modules\admin\grid\ActionColumn::className(),
                    'controller'            => $controller,
                    'isOpenNewWindow'       => true
                ],

            ],
        ], (array) $this->gridViewOptions);

        return $this->render('related-models-grid',[
            'widget'        => $this,
            'createUrl'     => $createUrl,
            'controller'    => $controller,
            'gridOptions'   => $gridOptions,
            'pjaxId'        => $pjaxId,
        ]);
    }
}