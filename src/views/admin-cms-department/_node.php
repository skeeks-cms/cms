<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 *
 * @var \skeeks\cms\models\CmsTree $model
 */

/* @var $this yii\web\View */
/* @var $widget \skeeks\cms\widgets\tree\CmsTreeWidget */
/* @var $model \skeeks\cms\models\CmsDepartment */
/*   */

$widget = $this->context;

$result = $model->name;
$additionalName = '';
/*if ($model->level == 0) {
    $site = \skeeks\cms\models\CmsSite::findOne(['id' => $model->cms_site_id]);
    if ($site) {
        $additionalName = $site->name;
    }
} else {
    if ($model->name_hidden) {
        $additionalName = $model->name_hidden;
    }
}*/

if ($additionalName) {
    $result .= " [{$additionalName}]";
}


?>

<div class="sx-department">
    <div class="sx-label-node">

        <? /* if ($widget->isOpenNode($model)) : */ ?><!--
            <i class="far fa-folder-open"></i>
        <? /* else : */ ?>
            <i class="far fa-folder"></i>
        --><? /* endif; */ ?>

        <!--<a href="<? /*= $widget->getOpenCloseLink($model); */ ?>">
            <? /*= $result; */ ?>
        </a>-->

        <?php $widget = \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::begin([
            'controllerId'            => '/cms/admin-cms-department',
            'modelId'                 => $model->id,
            /*'rightClickSelectors' => ['.sx-tree-node-'.$model->id],*/
            'isRunFirstActionOnClick' => true,
            'options'                 => [
                'tag'   => false,
                'class' => '',
                'style' => 'cursor: pointer; font-size: 1.5rem;',
            ],
        ]); ?>
        <?= $result; ?>
        <?php $widget::end(); ?>


    </div>
    <?php if($model->supervisor) : ?>
    <div>
        Руководитель: <?php echo $model->supervisor->shortDisplayName; ?>
    </div>
    <?php endif; ?>

    <?php if($model->workers) : ?>
    <div>
        Сотрудники: <?php echo implode(", ", \yii\helpers\ArrayHelper::map($model->workers, 'id', 'shortDisplayName')); ?>
    </div>
    <?php endif; ?>


    <div class="">
        <div>
            <a href="#" class="btn btn-default btn-sm add-tree-child"
               title="<?= \Yii::t('skeeks/cms', 'Create subsection'); ?>" data-id="<?= $model->id; ?>"><span
                        class="fa fa-plus"></span> Добавить отдел</a>
        </div>
    </div>


    <? /*= \skeeks\cms\backend\widgets\DropdownControllerActionsWidget::widget([
        "actions" => $controller->modelActions,
        "renderFirstAction" => true,
        "wrapperOptions" => ['class' => "dropdown pull-left"],
        'clientOptions' =>
            [
                'pjax-id' => $widget->pjax->id
            ]
    ]); */ ?>


    <?php if ($model->pid > 0) : ?>
        <a href="#" class="btn btn-default sx-tree-move" style="cursor: move;"
           title="<?= \Yii::t('skeeks/cms', "Change sorting"); ?>">
            <span class="fas fa-arrows-alt-v"></span>
        </a>
    <?php endif; ?>


</div>

