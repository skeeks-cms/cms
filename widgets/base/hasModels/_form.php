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
use skeeks\cms\widgets\base\hasModels\ActiveForm;

/* @var $this yii\web\View */
/* @var $model \skeeks\cms\models\WidgetConfig */
\Yii::$app->registeredModels->getComponents()
?>
<?php $form = ActiveForm::begin(); ?>
<?= $form->templateElement($model); ?>

<?= $form->field($model, 'modelClassName')->label('Модель (сущьность)')->widget(
    \skeeks\widget\chosen\Chosen::className(), [
        'items'   => \yii\helpers\ArrayHelper::map(
            \Yii::$app->registeredModels->getComponents(),
            "id",
             "name"
        ),
    ]);
?>

<?= $form->buttonsCreateOrUpdate($model); ?>
<?php ActiveForm::end(); ?>


