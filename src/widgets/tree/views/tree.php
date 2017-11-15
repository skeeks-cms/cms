<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 18.12.2016
 */
/* @var $this yii\web\View */
/* @var $widget \skeeks\cms\widgets\tree\CmsTreeWidget */
$widget = $this->context;
?>
<div class="row">
    <div class="sx-container-tree col-md-12">
        <?= \yii\helpers\Html::beginTag("div", $widget->options); ?>
        <?= $widget->renderNodes($widget->models); ?>
        <?= \yii\helpers\Html::endTag("div"); ?>
    </div>
</div>

