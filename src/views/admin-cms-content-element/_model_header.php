<?php
/**
 * @var yii\web\View $this
 * @var \skeeks\cms\models\CmsContentElement $model
 */

use skeeks\cms\widgets\admin\CmsPublicModelHeader;

$supplierUrl = $model->sx_id && isset(Yii::$app->skeeksSuppliersApi)
    ? Yii::$app->skeeksSuppliersApi->getProductUrl($model->sx_id)
    : null;
$tree = $model->tree_id ? $model->cmsTree : null;

echo CmsPublicModelHeader::widget([
    'model'        => $model,
    'publicUrl'    => $model->cmsContent->is_have_page ? $model->url : null,
    'supplierUrl'  => $supplierUrl,
    'parentLabel'  => $tree ? $tree->name : null,
    'parentTitle'  => $tree ? $tree->fullName : null,
    'showBackLink' => false,
]);
