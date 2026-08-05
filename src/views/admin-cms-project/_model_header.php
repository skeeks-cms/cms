<?php
/**
 * @var yii\web\View $this
 * @var \skeeks\cms\models\CmsProject $model
 */

use skeeks\cms\backend\helpers\BackendIcon;
use skeeks\cms\backend\helpers\BackendUrlHelper;
use skeeks\cms\backend\widgets\BackendEntityLink;
use skeeks\cms\backend\widgets\BackendModelHeader;
use skeeks\cms\backend\widgets\BackendQuickAccessFavoriteButton;
use yii\helpers\Html;
use yii\helpers\Url;

$imageSrc = $model->cmsImage ? (string)Yii::$app->imaging->thumbnailUrlOnRequest(
    $model->cmsImage->src,
    new \skeeks\cms\components\imaging\filters\Thumbnail([
        'w' => 80,
        'h' => 80,
        'm' => \Imagine\Image\ImageInterface::THUMBNAIL_OUTBOUND,
    ]),
    '',
    true
) : null;

$favorite = BackendQuickAccessFavoriteButton::widget([
    'item' => [
        'type'   => 'projects',
        'id'     => (int)$model->id,
        'name'   => trim((string)$model->name),
        'url'    => Url::to(['/cms/admin-cms-project/view', 'pk' => $model->id]),
        'action' => (string)BackendUrlHelper::createByParams([
            '/cms/admin-cms-project/view',
            'pk' => $model->id,
        ])->enableEmptyLayout()->enableNoActions()->url,
        'image'  => $imageSrc,
    ],
]);

$metaItems = [];
if ($model->cmsCompany) {
    $metaItems[] = Html::tag('span',
        BackendIcon::render('building', ['size' => 13]).' '.BackendEntityLink::widget([
            'controllerId' => '/cms/admin-cms-company',
            'modelId'      => $model->cmsCompany->id,
            'label'        => $model->cmsCompany->name,
        ])
    );
}

echo BackendModelHeader::widget([
    'model'        => $model,
    'title'        => $model->name,
    'titleSuffix'  => $favorite,
    'imageSrc'     => $imageSrc,
    'roundImage'   => true,
    'metaItems'    => $metaItems,
    'showBackLink' => false,
]);
