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


<? $alert = \yii\bootstrap\Alert::begin([
    'options' => [
        'class' => 'alert-default'
    ],
    'closeButton' => false,
]); ?>
    <p><?= \Yii::t('skeeks/cms', 'Erase all the settings from the database for this component.') ?></p>
    <?php if ($settingsAllCount = \skeeks\cms\models\CmsComponentSettings::findByComponent($component)->count()) : ?>
        <p><b><?= \Yii::t('skeeks/cms', 'Total found') ?>:</b> <?= $settingsAllCount; ?></p>
        <button type="submit" class="btn btn-danger btn-xs"
                onclick="sx.ComponentSettings.Remove.removeAll(); return false;">
            <i class="fa fa-times"></i> <?= \Yii::t('skeeks/cms', 'reset all settings') ?>
        </button>
    <?php else
        : ?>
        <small><?= \Yii::t('skeeks/cms', 'The database no settings for this component.') ?></small>
    <?php endif;
    ?>
<? $alert::end(); ?>


<?= $this->render('_footer'); ?>



