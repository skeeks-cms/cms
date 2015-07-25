<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 24.07.2015
 */
namespace skeeks\cms\modules\admin\widgets;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use skeeks\cms\modules\admin\grid\CheckboxColumn;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

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
        $checkbox = Html::checkbox('sx-select-full-all');

        $this->afterTableLeft = <<<HTML
    {$checkbox} для всех

    <span class="sx-admin-grid-controlls">
        <button class="btn btn-default btn-sm">Кнопка</button>
        <button class="btn btn-default btn-sm"><i class="glyphicon glyphicon-ok"></i> Применить</button>
    </span>
HTML;

        $this->view->registerJs(<<<JS

JS
);

        $this->view->registerCss(<<<CSS
    .sx-admin-grid-controlls
    {
        margin-left: 20px;
    }
CSS
);

        return parent::renderAfterTable();


    }

}