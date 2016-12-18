<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 18.12.2016
 */
/* @var $this yii\web\View */
/* @var $widget \skeeks\cms\widgets\tree\CmsTreeWidget */
/* @var $model \skeeks\cms\models\CmsTree */
$widget = $this->context;

$result = $model->name;
$additionalName = '';
if ($model->level == 0)
{
    $site = \skeeks\cms\models\CmsSite::findOne(['code' => $model->site_code]);
    if ($site)
    {
        $additionalName = $site->name;
    }
} else
{
    if ($model->name_hidden)
    {
        $additionalName = $model->name_hidden;
    }
}

if ($additionalName)
{
    $result .= " [{$additionalName}]";
}

?>
<div class="sx-label-node level-<?= $model->level; ?> status-<?= $model->active; ?>">
    <a href="<?= $widget->getOpenCloseLink($model); ?>">
        <?= $result; ?>
    </a>
</div>

<!-- Possible actions -->
<div class="sx-controll-node row">
    <?
        $controller = \Yii::$app->cms->moduleCms->createControllerByID("admin-tree");
        $controller->setModel($model);
    ?>
    <?= \skeeks\cms\modules\admin\widgets\DropdownControllerActions::widget([
        "controller"            => $controller,
        "renderFirstAction"     => true,
        "containerClass"        => "dropdown pull-left",
        'clientOptions'         =>
        [
            'pjax-id' => 'sx-pjax-tree'
        ]
    ]); ?>
    <div class="pull-left sx-controll-act">
        <a href="#" class="btn-tree-node-controll btn btn-default btn-sm add-tree-child" title="<?= \Yii::t('skeeks/cms','Create subsection'); ?>" data-id="<?= $model->id; ?>"><span class="glyphicon glyphicon-plus"></span></a>
    </div>
    <div class="pull-left sx-controll-act">
        <a href="<?= $model->absoluteUrl; ?>" target="_blank" class="btn-tree-node-controll btn btn-default btn-sm show-at-site" title="<?= \Yii::t('skeeks/cms',"Show at site"); ?>">
            <span class="glyphicon glyphicon-eye-open"></span>
        </a>
    </div>
    <? if ($model->level > 0) : ?>
        <div class="pull-left sx-controll-act">
            <a href="#" class="btn-tree-node-controll btn btn-default btn-sm sx-tree-move" title="<?= \Yii::t('skeeks/cms',"Change sorting"); ?>">
                <span class="glyphicon glyphicon-move"></span>
            </a>
        </div>
    <? endif; ?>
</div>

<? if ($model->treeType) : ?>
    <div class="pull-right sx-tree-type">
        <?= $model->treeType->name; ?>
    </div>
<? endif; ?>

