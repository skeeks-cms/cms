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
use skeeks\cms\widgets\base\hasTemplate\ActiveForm;

$tree = new \skeeks\cms\models\Tree();

/* @var $this yii\web\View */
/* @var $model \skeeks\cms\models\WidgetConfig */

?>
<?php $form = ActiveForm::begin(); ?>

<?= $form->templateElement($model); ?>


<?= $form->field($model, 'title')->textInput(); ?>


<?= $form->field($model, 'tree_ids')->widget(
    \skeeks\widget\chosen\Chosen::className(), [
        'items' => \skeeks\cms\models\helpers\Tree::getAllMultiOptions(),
        'multiple' => true
    ]);
?>


<?= $form->field($model, 'limit')->textInput(); ?>

<?= $form->field($model, 'types')->widget(
    \skeeks\widget\chosen\Chosen::className(), [
        'items'   => \yii\helpers\ArrayHelper::map(
            (new \skeeks\cms\models\Publication())->getDescriptor()->getTypes()->getComponents(),
            "id",
             "name"
        ),
        'multiple' => true,
    ]);
?>
<?= $form->field($model, 'statuses')->widget(
    \skeeks\widget\chosen\Chosen::className(), [
        'items' => $tree->getPossibleStatuses(),
        'multiple' => true,
    ]);
?>

<?= $form->field($model, 'statusesAdults')->widget(
    \skeeks\widget\chosen\Chosen::className(), [
        'items' => $tree->getPossibleAdultStatuses(),
        'multiple' => true,
    ]);
?>

<?= $form->buttonsCreateOrUpdate($model); ?>
<?php ActiveForm::end(); ?>


