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


<?= $form->buttonsCreateOrUpdate($model); ?>
<?php ActiveForm::end(); ?>


