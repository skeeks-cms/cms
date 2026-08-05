<?php
/**
 * @var yii\web\View $this
 * @var \skeeks\cms\models\CmsTask $model
 */

use skeeks\cms\backend\helpers\BackendIcon;
use skeeks\cms\backend\widgets\BackendEntityLink;
use skeeks\cms\backend\widgets\BackendModelHeader;
use yii\helpers\Html;

$image = null;
if ($model->cmsProject && $model->cmsProject->cmsImage) {
    $image = $model->cmsProject->cmsImage;
} elseif ($model->cmsCompany && $model->cmsCompany->cmsImage) {
    $image = $model->cmsCompany->cmsImage;
}

$imageSrc = $image ? (string)Yii::$app->imaging->thumbnailUrlOnRequest(
    $image->src,
    new \skeeks\cms\components\imaging\filters\Thumbnail([
        'w' => 80,
        'h' => 80,
        'm' => \Imagine\Image\ImageInterface::THUMBNAIL_OUTBOUND,
    ]),
    '',
    true
) : null;

$metaItems = [];
if ($model->cmsProject) {
    $metaItems[] = Html::tag('span',
        BackendIcon::render('tasks', ['size' => 13]).' '.BackendEntityLink::widget([
            'controllerId' => '/cms/admin-cms-project',
            'modelId'      => $model->cmsProject->id,
            'label'        => $model->cmsProject->name,
        ])
    );
}
if ($model->cmsCompany) {
    $metaItems[] = Html::tag('span',
        BackendIcon::render('building', ['size' => 13]).' '.BackendEntityLink::widget([
            'controllerId' => '/cms/admin-cms-company',
            'modelId'      => $model->cmsCompany->id,
            'label'        => $model->cmsCompany->asText,
        ])
    );
}
if ($model->cmsUser) {
    $metaItems[] = Html::tag('span',
        BackendIcon::render('user', ['size' => 13]).' '.BackendEntityLink::widget([
            'controllerId' => '/cms/admin-user',
            'modelId'      => $model->cmsUser->id,
            'label'        => $model->cmsUser->shortDisplayName,
        ])
    );
}

echo BackendModelHeader::widget([
    'model'        => $model,
    'title'        => $model->asText,
    'imageSrc'     => $imageSrc,
    'roundImage'   => true,
    'metaItems'    => $metaItems,
    'showBackLink' => false,
]);
