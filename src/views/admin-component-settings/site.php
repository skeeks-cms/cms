<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 27.03.2015
 *
 * @var $component \skeeks\cms\base\Component
 * @var $site \skeeks\cms\models\CmsSite
 */
/* @var $this yii\web\View */
$controller = $this->context;
?>

<?= $this->render('_header', [
    'component' => $component
]); ?>


<h2><?= \Yii::t('skeeks/cms', 'Settings for the site') ?>: <?= $site->name; ?></h2>
<div class="sx-box g-mb-10">
    <? $alert = \yii\bootstrap\Alert::begin([
        'options' => [
            'class' => 'alert-default'
        ],
        'closeButton' => false,
    ]); ?>
    <?php if ($settings = \skeeks\cms\models\CmsComponentSettings::findByComponentSite($component, $site)->one()) : ?>
        <button type="submit" class="btn btn-danger btn-xs"
                onclick="sx.ComponentSettings.Remove.removeBySite('<?= $site->id; ?>'); return false;">
            <i class="fa fa-times"></i> <?= \Yii::t('skeeks/cms', 'reset settings for this site') ?>
        </button>
        <small><?= \Yii::t('skeeks/cms',
                'The settings for this component are stored in the database. This option will erase them from the database, but the component, restore the default values. As they have in the code the developer.') ?></small>
    <?php else
        : ?>
        <small><?= \Yii::t('skeeks/cms', 'These settings not yet saved in the database') ?></small>
    <?php endif;
    ?>
    <? $alert::end(); ?>
</div>

<?= $this->render('_form', [
    'component' => $component,
    'controller' => $controller,
]); ?>


<?= $this->render('_footer'); ?>
