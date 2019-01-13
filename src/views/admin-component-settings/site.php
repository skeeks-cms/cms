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
?>

<?= $this->render('_header', [
    'component' => $component
]); ?>


<h2><?= \Yii::t('skeeks/cms', 'Settings for the site') ?>: <?= $site->name; ?> (<?= $site->code; ?>)</h2>
<div class="sx-box sx-mb-10 sx-p-10">
    <?php if ($settings = \skeeks\cms\models\CmsComponentSettings::findByComponentSite($component, $site)->one()) : ?>
        <button type="submit" class="btn btn-danger btn-xs"
                onclick="sx.ComponentSettings.Remove.removeBySite('<?= $site->code; ?>'); return false;">
            <i class="fa fa-times"></i> <?= \Yii::t('skeeks/cms', 'reset settings for this site') ?>
        </button>
        <small><?= \Yii::t('skeeks/cms',
                'The settings for this component are stored in the database. This option will erase them from the database, but the component, restore the default values. As they have in the code the developer.') ?></small>
    <?php else
        : ?>
        <small><?= \Yii::t('skeeks/cms', 'These settings not yet saved in the database') ?></small>
    <?php endif;
    ?>
</div>


<?php $form = \skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab::begin(); ?>

<?= $form->errorSummary(\yii\helpers\ArrayHelper::merge(
        [$component], $component->getConfigFormModels()
)); ?>

<? if ($fields = $component->getConfigFormFields()) : ?>
    <? echo (new \skeeks\yii2\form\Builder([
        'models'     => $component->getConfigFormModels(),
        'model'      => $component,
        'activeForm' => $form,
        'fields'     => $fields,
    ]))->render(); ?>
<? elseif ($formContent = $component->renderConfigForm($form)) : ?>
    <?= $formContent; ?>
<? else : ?>
    Нет редактируемых настроек для данного компонента
<? endif; ?>

<?= $form->buttonsStandart($component); ?>
<?= $form->errorSummary(\yii\helpers\ArrayHelper::merge(
        [$component], $component->getConfigFormModels()
)); ?>

<?php \skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab::end(); ?>


<?= $this->render('_footer'); ?>
