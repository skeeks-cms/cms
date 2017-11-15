<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 26.09.2015
 */
$imageFile = $model;
?>
<a href="<?= $imageFile->src; ?>" class="sx-fancybox" data-pjax="0">
    <img src="<?= \Yii::$app->imaging->getImagingUrl($imageFile->src,
        new \skeeks\cms\components\imaging\filters\Thumbnail()); ?>"/>
</a>
<div class="sx-controlls">
    <?= \yii\helpers\Html::a('<i class="glyphicon glyphicon-circle-arrow-left"></i> ' . \Yii::t('skeeks/cms',
            'Choose file'), $model->src, [
        'class' => 'btn btn-primary btn-xs',
        'onclick' => 'sx.SelectFile.submit("' . $model->src . '"); return false;',
        'data-pjax' => 0
    ]); ?>
</div>