<?php

use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\ActiveForm;
use common\models\User;

/* @var $this yii\web\View */
/* @var $model \yii\db\ActiveRecord */
/* @var $console \skeeks\cms\controllers\AdminUserController */
?>


<?php $form = ActiveForm::begin(); ?>
<?php  ?>

<? if ($content_type = \Yii::$app->request->get('content_type')) : ?>
    <?= $form->field($model, 'content_type')->hiddenInput(['value' => $content_type])->label(false); ?>
<? else: ?>
    <div style="display: none;">
        <?= $form->fieldSelect($model, 'content_type', \yii\helpers\ArrayHelper::map(\skeeks\cms\models\CmsContentType::find()->all(), 'code', 'name')); ?>
    </div>
<? endif; ?>

<?= $form->field($model, 'name')->textInput(); ?>
<?= $form->field($model, 'code')->textInput(); ?>
<?= $form->fieldRadioListBoolean($model, 'active'); ?>
<?= $form->fieldInputInt($model, 'priority'); ?>

<?= $form->buttonsCreateOrUpdate($model); ?>
<?php ActiveForm::end(); ?>
