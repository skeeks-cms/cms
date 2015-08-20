<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 27.03.2015
 */
use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab as ActiveForm;

/* @var $this yii\web\View */
/* @var $model \skeeks\cms\models\WidgetConfig */

?>
    <?php $form = ActiveForm::begin(); ?>

        <?= $form->fieldSet('Общие'); ?>
            <?= $form->field($model, 'enabled')->radioList(\Yii::$app->formatter->booleanFormat); ?>
        <?= $form->fieldSetEnd(); ?>

        <?= $form->fieldSet('GitHub'); ?>

            <p>Создайте приложение на странице: <?= Html::a('https://github.com/settings/applications', 'https://github.com/settings/applications'); ?>, и получите его настройки.</p>
            <hr />

            <?= $form->field($model, 'githubEnabled')->radioList(\Yii::$app->formatter->booleanFormat); ?>

            <?= $form->field($model, 'githubClientId')->textInput(['placeholder' => 'c692de6c3c3247e39cf4']); ?>
            <?= $form->field($model, 'githubClientSecret')->textInput(['placeholder' => 'f01f7bc7d41f38e4049d15786c0f1b93a5e96e90']); ?>
            <?= $form->field($model, 'githubClass')->textInput(['placeholder' => 'yii\authclient\clients\GitHub'])->hint('Необязательный параметр, если не заполнен будет использован yii\authclient\clients\GitHub'); ?>

        <?= $form->fieldSetEnd(); ?>

    <?= $form->buttonsCreateOrUpdate($model); ?>
<?php ActiveForm::end(); ?>


