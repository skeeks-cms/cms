<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 27.03.2015
 *
 * @var $component \skeeks\cms\base\Component
 * @var $user \skeeks\cms\models\User
 */
/* @var $this yii\web\View */
?>

<?= $this->render('_header', [
    'component' => $component
]); ?>


    <h2><?=\Yii::t('skeeks/cms','User settings')?>: <?= $user->getDisplayName() ?></h2>
    <div class="sx-box sx-mb-10 sx-p-10">
        <? if ($settings = \skeeks\cms\models\CmsComponentSettings::fetchByComponentUserId($component, $user->id)) : ?>
            <button type="submit" class="btn btn-danger btn-xs" onclick="sx.ComponentSettings.Remove.removeByUser('<?= $user->id; ?>'); return false;">
                <i class="glyphicon glyphicon-remove"></i> <?=\Yii::t('skeeks/cms','Reset settings for this user')?>
            </button>
            <small><?=\Yii::t('skeeks/cms','The settings for this component are stored in the database. This option will erase them from the database, but the component, restore the default values. As they have in the code the developer.')?></small>
        <? else: ?>
            <small><?=\Yii::t('skeeks/cms','These settings not yet saved in the database')?></small>
        <? endif; ?>
    </div>

    <? $form = \skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab::begin(); ?>
        <?= $component->renderConfigForm($form); ?>
        <?= $form->buttonsStandart($component); ?>
    <? \skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab::end(); ?>


<?= $this->render('_footer'); ?>
