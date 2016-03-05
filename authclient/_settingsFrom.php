<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 27.03.2015
 */
use yii\helpers\Html;
/* @var $this yii\web\View */
/* @var $model \skeeks\cms\models\WidgetConfig */

?>
<?= $form->fieldSet(\Yii::t('app','Are common')); ?>
    <?= $form->field($model, 'enabled')->radioList(\Yii::$app->formatter->booleanFormat); ?>
<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet('GitHub'); ?>

    <p><?=\Yii::t('app','Create application at page')?>: <?= Html::a('https://github.com/settings/applications', 'https://github.com/settings/applications', [
            'target' => '_blank'
        ]); ?><?=\Yii::t('app',', and get its settings.')?></p>
    <hr />

    <?= $form->field($model, 'githubEnabled')->radioList(\Yii::$app->formatter->booleanFormat); ?>

    <?= $form->field($model, 'githubClientId')->textInput(['placeholder' => 'c692de6c3c3247e39cf4']); ?>
    <?= $form->field($model, 'githubClientSecret')->textInput(['placeholder' => 'f01f7bc7d41f38e4049d15786c0f1b93a5e96e90']); ?>
    <?= $form->field($model, 'githubClass')->textInput(['placeholder' => 'yii\authclient\clients\GitHub'])->hint(\Yii::t('app','Optional parameter, if not filled will be used {yii}',['yii' => 'yii\authclient\clients\GitHub'])); ?>

<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet('Vk'); ?>

    <p><?=\Yii::t('app','Create application at page')?>: <?= Html::a('http://vk.com/editapp?act=create', 'http://vk.com/editapp?act=create', [
            'target' => '_blank'
        ]); ?><?=\Yii::t('app',', and get its settings.')?></p>
    <hr />

    <?= $form->field($model, 'vkEnabled')->radioList(\Yii::$app->formatter->booleanFormat); ?>

    <?= $form->field($model, 'vkClientId')->textInput(['placeholder' => '5040380']); ?>
    <?= $form->field($model, 'vkClientSecret')->textInput(['placeholder' => 'sxAWws6ATNj5vDabPysA']); ?>
    <?= $form->field($model, 'vkClass')->textInput(['placeholder' => 'yii\authclient\clients\VKontakte'])->hint(\Yii::t('app','Optional parameter, if not filled will be used {yii}',['yii' => 'yii\authclient\clients\VKontakte'])); ?>

<?= $form->fieldSetEnd(); ?>


<?= $form->fieldSet('Facebook'); ?>

    <p><?=\Yii::t('app','Create application at page')?>: <?= Html::a('https://developers.facebook.com/apps', 'https://developers.facebook.com/apps', [
            'target' => '_blank'
        ]); ?><?=\Yii::t('app',', and get its settings.')?></p>
    <hr />

    <?= $form->field($model, 'facebookEnabled')->radioList(\Yii::$app->formatter->booleanFormat); ?>

    <?= $form->field($model, 'facebookClientId')->textInput(['placeholder' => '5040380']); ?>
    <?= $form->field($model, 'facebookClientSecret')->textInput(['placeholder' => 'sxAWws6ATNj5vDabPysA']); ?>
    <?= $form->field($model, 'facebookClass')->textInput(['placeholder' => 'yii\authclient\clients\Facebook'])->hint(\Yii::t('app','Optional parameter, if not filled will be used {yii}',['yii' => 'yii\authclient\clients\VKontakte'])); ?>

<?= $form->fieldSetEnd(); ?>



