<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */
/* @var $this yii\web\View */
/* @var $user \skeeks\cms\models\CmsUser */

use skeeks\cms\backend\widgets\BackendEntityLink;
use skeeks\cms\helpers\Image;
use yii\helpers\Html;

$name = (string)$user->shortDisplayName;
$content = Html::tag(
    'span',
    Html::img($user->avatarSrc ?: Image::getCapSrc(), [
        'class' => 'sx-photo sx-img-size-small',
        'alt'   => '',
    ]),
    ['class' => 'sx-preview-card__media']
)
    .Html::tag(
        'span',
        Html::tag('span', Html::encode($name), [
            'class' => 'sx-collection-cell__primary',
        ]),
        ['class' => 'sx-preview-card__content sx-collection-cell sx-collection-cell--stack']
    );
?>
<?= BackendEntityLink::widget([
    'controllerId' => '/cms/admin-user',
    'modelId'      => $user->id,
    'content'      => $content,
    'options'      => [
        'class'      => 'sx-preview-card sx-preview-card--person sx-preview-card__title',
        'aria-label' => $name,
    ],
]); ?>
