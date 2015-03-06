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
use skeeks\cms\widgets\base\hasModelsSmart\ActiveForm;

$tree = new \skeeks\cms\models\Tree();

/* @var $this yii\web\View */
/* @var $model \skeeks\cms\models\WidgetConfig */
?>
<?php $form = ActiveForm::begin(); ?>


<?= $form->standartElements($model); ?>



<?= $form->fieldSet('Дополнительные фильтры'); ?>
    <?= $form->field($model, 'useCurrentTree')->label('Показывать публикации привязанные к разделу, где находится пользователь')->widget(
        \skeeks\widget\chosen\Chosen::className(), [
            'items'   => [
                '0' => 'нет',
                '1' => 'да',
            ]
        ]);
    ?>
<?= $form->field($model, 'types')->label('Типы публикаций')->widget(
        \skeeks\widget\chosen\Chosen::className(), [
            'items'   => \yii\helpers\ArrayHelper::map(
                (new \skeeks\cms\models\Publication())->getDescriptor()->getTypes()->getComponents(),
                "id",
                 "name"
            ),
            'multiple' => true,
        ]);
    ?>
    <?= $form->field($model, 'statuses')->label('Статусы')->widget(
        \skeeks\widget\chosen\Chosen::className(), [
            'items' => $tree->getPossibleStatuses(),
            'multiple' => true,
        ]);
    ?>

    <?= $form->field($model, 'statusesAdults')->label('Возрсатные статусы')->widget(
        \skeeks\widget\chosen\Chosen::className(), [
            'items' => $tree->getPossibleAdultStatuses(),
            'multiple' => true,
        ]);
    ?>

    <?= $form->field($model, 'createdBy')->label('Авторы')->widget(
        \skeeks\widget\chosen\Chosen::className(), [
            'items' => \yii\helpers\ArrayHelper::map(
                \common\models\User::find()->all(),
                'id',
                'username'
            ),
            'multiple' => true,
        ]);
    ?>
<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet('Прочее'); ?>
    <?= $form->field($model, 'title')->label('Заголовок')->textInput(); ?>
<?= $form->fieldSetEnd(); ?>

<?= $form->buttonsCreateOrUpdate($model); ?>
<?php ActiveForm::end(); ?>


