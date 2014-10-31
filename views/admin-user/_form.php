<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use common\models\User;

/* @var $this yii\web\View */
/* @var $model common\models\Game */
/* @var $form yii\widgets\ActiveForm */
?>


<?php $form = ActiveForm::begin(); ?>

<?/*= $form->field($model, 'user_id')->widget('yii\jui\AutoComplete',[
    'options'=>['class'=>'form-control'],
    'clientOptions'=>[
        'source'=>  User::find()->select(['id'])->column()
    ]
]) */?>


<?/*= $form->field($model, 'image')
    ->widget(\backend\widgets\storageFileManager\Widget::className(), []);
*/?><!--

<?/*= $form->field($model, 'image_cover')
    ->widget(\backend\widgets\storageFileManager\Widget::className(), []);
*/?>

<?/*= $form->field($model, 'images')
    ->widget(\backend\widgets\storageFileManager\Widget::className(), []);
*/?>

--><?/*= $form->field($model, 'files')
    ->widget(\backend\widgets\storageFileManager\Widget::className(), []);
*/?>


<?= $form->field($model, 'name')->textInput(['maxlength' => 255]) ?>

<?= $form->field($model, 'description_short')->widget(\skeeks\widget\ckeditor\CKEditor::className(), [
    'options' => ['rows' => 6],
    'preset' => 'basic'
]) ?>

<?= $form->field($model, 'description_full')->widget(\skeeks\widget\ckeditor\CKEditor::className(), [
    'options' => ['rows' => 20],
    'preset' => 'full'
]) ?>


<?= $form->field($model, 'genre_ids')->widget(
    \skeeks\widget\chosen\Chosen::className(), [
            'items' => \yii\helpers\ArrayHelper::map(
                 \common\models\GameGenre::find()->asArray()->all(),
                 "id",
                 "name"
             ),
            'multiple' => true
    ]);
?>


<?= $form->field($model, 'platform_ids')->widget(
    \skeeks\widget\chosen\Chosen::className(), [
            'items' => \yii\helpers\ArrayHelper::map(
                 \common\models\GamePlatform::find()->asArray()->all(),
                 "id",
                 "name"
             ),
            'multiple' => true
    ]);
?>


<?= $form->field($model, 'developer_ids')->widget(
    \skeeks\widget\chosen\Chosen::className(), [
            'items' => \yii\helpers\ArrayHelper::map(
                 \common\models\GameCompany::find()->asArray()->all(),
                 "id",
                 "name"
             ),
            'multiple' => true
    ]);
?>


<?= $form->field($model, 'publisher_ids')->widget(
    \skeeks\widget\chosen\Chosen::className(), [
            'items' => \yii\helpers\ArrayHelper::map(
                 \common\models\GameCompany::find()->asArray()->all(),
                 "id",
                 "name"
             ),
            'multiple' => true
    ]);
?>

<!--

<?/*= $form->field($model, 'created_by')->widget(
    \nex\chosen\Chosen::className(), [
            'items' => \yii\helpers\ArrayHelper::map(
                             User::find()->asArray()->all(),
                             "id",
                             "username"
                         ),
    ]);
*/?><!--

--><?/*= $form->field($model, 'updated_by')->widget(
    \nex\chosen\Chosen::className(), [
            'items' => \yii\helpers\ArrayHelper::map(
                             User::find()->asArray()->all(),
                             "id",
                             "username"
                         ),
    ]);
*/?>

<div class="form-group">
    <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
</div>

<?php ActiveForm::end(); ?>

