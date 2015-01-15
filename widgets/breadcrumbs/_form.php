<?php
/**
 * _form
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 09.11.2014
 * @since 1.0.0
 */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$tree = new \skeeks\cms\models\Tree();

/* @var $this yii\web\View */
/* @var $form yii\widgets\ActiveForm */
/* @var $model \skeeks\cms\models\WidgetConfig */

?>
<?php $form = ActiveForm::begin(); ?>

<?= $form->field($model, 'template')->widget(
    \skeeks\widget\chosen\Chosen::className(), [
            'items' => \yii\helpers\ArrayHelper::map(
                 $model->getWidgetDescriptor()->getTemplatesObject()->getComponents(),
                 "id",
                 "name"
             ),
    ]);
?>


<?= $form->field($model, 'title')->textInput(); ?>


<div class="form-group">
    <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
</div>
<?php ActiveForm::end(); ?>


