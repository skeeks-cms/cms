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
use skeeks\cms\modules\admin\widgets\ActiveForm;

/* @var $this yii\web\View */
?>
<?php $form = ActiveForm::begin(); ?>
<?= $form->field($model, 'text')->textarea([
    'rows' => '15'
]); ?>
<?= $form->buttonsCreateOrUpdate($model) ?>
<?php ActiveForm::end(); ?>

