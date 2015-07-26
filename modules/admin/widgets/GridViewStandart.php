<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 24.07.2015
 */
namespace skeeks\cms\modules\admin\widgets;
use skeeks\cms\modules\admin\actions\modelEditor\AdminMultiModelEditAction;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use skeeks\cms\modules\admin\grid\CheckboxColumn;
use skeeks\cms\modules\admin\widgets\gridViewStandart\GridViewStandartAsset;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\JsExpression;

/**
 * Class GridViewStandart
 * @package skeeks\cms\modules\admin\widgets
 */
class GridViewStandart extends GridViewHasSettings
{
    /**
     * @var AdminModelEditorController
     */
    public $adminController = null;

    public function init()
    {
        $this->columns = ArrayHelper::merge([
            ['class' => 'skeeks\cms\modules\admin\grid\CheckboxColumn'],
            [
                'class'         => \skeeks\cms\modules\admin\grid\ActionColumn::className(),
                'controller'    => $this->adminController
            ],
            [
                'class' => 'yii\grid\SerialColumn',
                'visible' => false
            ],
        ], $this->columns);

        parent::init();
    }

    /**
     * @return string
     */
    public function renderAfterTable()
    {
        $id = $this->id;

        GridViewStandartAsset::register($this->view);

        $checkbox = Html::checkbox('sx-select-full-all', false, [
            'class' => 'sx-select-full-all'
        ]);

        $multiActions = $this->adminController->getMultiActions();
        if (!$multiActions)
        {
            return parent::renderAfterTable();
        }

        $buttons = "";
        
        foreach ($multiActions as $action)
        {
            $actionsData[$action->id] = $this->getActionData($action);

            $buttons .= <<<HTML
            <button class="btn btn-default btn-sm sx-grid-multi-btn" data-id="{$action->id}"><i class="{$action->icon}"></i> {$action->name}</button>
HTML;
        }

        $this->afterTableLeft = <<<HTML
    {$checkbox} для всех
    <span class="sx-grid-multi-controlls">
        {$buttons}
    </span>
HTML;


        $options = [
            'id'                    => $this->id,
            'enabledPjax'           => $this->enabledPjax,
            'pjaxId'                => $this->pjax->id,
            'actions'               => $actionsData,
            'requestPkParamName'    => $this->adminController->requestPkParamName
        ];
        $optionsString = Json::encode($options);

        $this->view->registerJs(<<<JS
        new sx.classes.grid.Standart($optionsString);
JS
);

        $this->view->registerCss(<<<CSS
    .sx-grid-multi-controlls
    {
        margin-left: 20px;
    }
CSS
);

        return parent::renderAfterTable();


    }


    /**
     * @param AdminMultiModelEditAction $action
     * @return array
     */
    public function getActionData($action)
    {
        $actionData = [
            "id"                => $action->id,
            "url"               => (string) $action->url,
            "confirm"           => $action->confirm,
            "method"            => $action->method,
            "request"           => $action->request,
        ];

        return $actionData;
    }



}