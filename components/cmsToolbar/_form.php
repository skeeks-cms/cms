<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 27.03.2015
 */
use yii\helpers\Html;
use skeeks\cms\widgets\base\hasModelsSmart\ActiveForm;

/* @var $this yii\web\View */
/* @var $model \skeeks\cms\models\WidgetConfig */
?>
<?php $form = ActiveForm::begin(); ?>


<?= $form->fieldSet('Основное'); ?>

    <?= $form->field($model, 'enabled')->widget(
        \skeeks\widget\chosen\Chosen::className(),
        [
            'items' => [
                1 => 'Включена',
                0 => 'Выключена'
            ]
        ]
    )->hint('Этот параметр отключает/включает панель для всех пользователей сайта, независимо от их прав и возможностей'); ?>

    <?= $form->field($model, 'mode')->widget(
        \skeeks\widget\chosen\Chosen::className(),
        [
            'items' => [
                \skeeks\cms\components\CmsToolbar::EDIT_MODE => 'Включен',
                \skeeks\cms\components\CmsToolbar::NO_EDIT_MODE => 'Выключен'
            ]
        ]
    )->hint('Режим редактирования сайта по умолчаню, изначально.'); ?>

<?= $form->fieldSetEnd(); ?>

<?= $form->buttonsCreateOrUpdate($model); ?>
<?php ActiveForm::end(); ?>


