<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 27.03.2015
 *
 * @var $component \skeeks\cms\base\Component
 */
/* @var $this yii\web\View */
?>

<?= $this->render('_header', [
    'component' => $component
]); ?>


<div class="sx-box sx-mb-10 sx-p-10">
    <p><?= \Yii::t('skeeks/cms', 'To improve performance, configure each component of the site is cached.') ?></p>
    <button type="submit" class="btn btn-danger btn-xs" onclick="sx.ComponentSettings.Cache.clearAll(); return false;">
        <i class="fa fa-times"></i> Сбросить кэш для всех
    </button>
</div>

<?= $this->render('_footer'); ?>



