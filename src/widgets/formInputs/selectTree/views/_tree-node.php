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
/* @var $selectTreeInputWidget \skeeks\cms\widgets\formInputs\selectTree\DaterangeInputWidget */
$widget = $this->context;
$selectTreeInputWidget = \yii\helpers\ArrayHelper::getValue($widget->contextData, 'selectTreeInputWidget');
?>

<?= $selectTreeInputWidget->renderNodeControll($model); ?>

<div class="sx-label-node level-<?= $model->level; ?> status-<?= $model->active; ?>">
    <? if ($model->level == 0) : ?>
        <i class="fas fa-home"></i>
    <? elseif ($model->redirectTree) : ?>
        <i class="fas fa-directions" data-toggle="tooltip" title="<?= $model->redirect_code ?> редирект в раздел: <?= $model->redirectTree->fullName; ?>"></i>
    <? elseif ($model->redirect) : ?>
        <i class="fas fa-directions" data-toggle="tooltip" title="<?= $model->redirect_code ?> редирект по url: <?= $model->redirect; ?>"></i>
    <? elseif ($widget->isOpenNode($model)) : ?>
        <i class="far fa-folder-open"></i>
    <? else : ?>
        <i class="far fa-folder"></i>
    <? endif; ?>
    
    <a href="<?= $widget->getOpenCloseLink($model); ?>"><?= $selectTreeInputWidget->renderNodeName($model); ?></a>
</div>

<!-- Possible actions -->
<!--<div class="sx-controll-node row">
    <div class="pull-left sx-controll-act">
        <a href="<?php /*= $model->absoluteUrl; */ ?>" target="_blank" class="btn-tree-node-controll btn btn-default btn-sm show-at-site" title="<?php /*= \Yii::t('skeeks/cms',"Show at site"); */ ?>">
            <span class="fa fa-eye"></span>
        </a>
    </div>
</div>-->

<?php /* if ($model->treeType) : */ ?><!--
    <div class="pull-right sx-tree-type">
        <?php /*= $model->treeType->name; */ ?>
    </div>
--><?php /* endif; */ ?>

