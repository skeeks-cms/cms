<?php

use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab as ActiveForm;
use skeeks\cms\models\Tree;

/* @var $this yii\web\View */
/* @var $model Tree */
?>


<?php $form = ActiveForm::begin(); ?>

    <?= $form->fieldSet(\Yii::t('skeeks/cms',"Main")); ?>

            <? if ($code = \Yii::$app->request->get('site_code')) : ?>
                <?= $form->field($model, 'site_code')->hiddenInput(['value' => $code])->label(false); ?>
            <? else: ?>
                <?= $form->field($model, 'site_code')->widget(
                    \skeeks\widget\chosen\Chosen::className(), [
                        'items' => \yii\helpers\ArrayHelper::map(
                             \skeeks\cms\models\CmsSite::find()->all(),
                             "code",
                             "name"
                        ),
                ]);
            ?>
            <? endif; ?>

        <?= $form->field($model, 'domain')->textInput(); ?>

    <?= $form->fieldSetEnd(); ?>
<?= $form->buttonsStandart($model) ?>

<?php ActiveForm::end(); ?>