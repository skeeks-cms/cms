<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */
/* @var $this yii\web\View */
/* @var $user \skeeks\cms\models\CmsUser */
?>
<? $widget = \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::begin([
    'controllerId' => 'cms/admin-user',
    'modelId' => $user->id
]); ?>
<div class="d-flex flex-row">
    <div class="my-auto" style="margin-right: 5px;">
        <img src='<?= $user->avatarSrc ? $user->avatarSrc : \skeeks\cms\helpers\Image::getCapSrc(); ?>' style='max-width: 25px; max-height: 25px; border-radius: 50%;'/>
    </div>
    <div class="my-auto">
        <div style="overflow: hidden; max-height: 40px; text-align: left;">
            <?= $user->shortDisplayName; ?>
        </div>
    </div>
</div>
<? $widget::end(); ?>
