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
if ($model->level == 0) {
    $site = \skeeks\cms\models\CmsSite::findOne(['id' => $model->cms_site_id]);
    if ($site) {
        $additionalName = $site->name;
    }
} else {
    if ($model->name_hidden) {
        $additionalName = $model->name_hidden;
    }
}

if ($additionalName) {
    $result .= " [{$additionalName}]";
}

$controllElement = \Yii::$app->controller->renderNodeControll($model);
?>

<?= $controllElement; ?>
<div class="sx-label-node level-<?= $model->level; ?> status-<?= $model->active; ?>">
    <a href="<?= $widget->getOpenCloseLink($model); ?>">
        <?= $result; ?>
    </a>
</div>

<!-- Possible actions -->
<div class="sx-controll-node row">
    <div class="pull-left sx-controll-act">
        <a href="<?= $model->absoluteUrl; ?>" target="_blank"
           class="btn-tree-node-controll btn btn-default btn-sm show-at-site"
           title="<?= \Yii::t('skeeks/cms', "Show at site"); ?>">
            <span class="fa fa-eye"></span>
        </a>
    </div>
</div>

<?php if ($model->treeType) : ?>
    <div class="pull-right sx-tree-type">
        <?= $model->treeType->name; ?>
    </div>
<?php endif; ?>

