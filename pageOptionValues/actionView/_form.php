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

/* @var $this yii\web\View */
/* @var $form yii\widgets\ActiveForm */
?>

<?php $form = \skeeks\cms\modules\admin\widgets\ActiveForm::begin(); ?>
<?= $form->field($model, 'value')->widget(
    \skeeks\widget\chosen\Chosen::className(), [
            'items' => \yii\helpers\ArrayHelper::map(
                 \Yii::$app->registeredModels->getDescriptor($modelEntity)->getActionViews()->getComponents(),
                 "id",
                 "name"
             ),
    ]);
?>

<?= $form->buttonsCreateOrUpdate($model); ?>
<?php \skeeks\cms\modules\admin\widgets\ActiveForm::end(); ?>

