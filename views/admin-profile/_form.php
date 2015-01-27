<?php
/**
 * form
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 06.11.2014
 * @since 1.0.0
 */
/* @var $this yii\web\View */


use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\Game */
?>


<?php $form = ActiveForm::begin(); ?>

<?/*= $form->field($model, 'group_id')->widget(
    \skeeks\widget\chosen\Chosen::className(), [
            'items' => \yii\helpers\ArrayHelper::map(
                 \skeeks\cms\models\UserGroup::find()->asArray()->all(),
                 "id",
                 "groupname"
             ),
    ]);
*/?>

<?= $form->field($model, 'username')->textInput(['maxlength' => 12]) ?>
<?= $form->field($model, 'name')->textInput(); ?>
<?= $form->field($model, 'city')->textInput(); ?>
<?= $form->field($model, 'address')->textInput(); ?>
<?= $form->field($model, 'info')->textarea(); ?>
<?= $form->field($model, 'status_of_life')->textarea(); ?>

<?= $form->buttonsCreateOrUpdate($model); ?>
<?php ActiveForm::end(); ?>

