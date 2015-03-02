<?php

use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\ActiveForm;
use skeeks\cms\models\Tree;
use skeeks\cms\modules\admin\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $model Tree */
?>

<?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'files')->widget(\skeeks\cms\widgets\formInputs\StorageImages::className())->label(false); ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => 255]) ?>

    <?= $form->field($model, 'type')->label('Тип публикации')->widget(
        \skeeks\widget\chosen\Chosen::className(), [
                'items' => \yii\helpers\ArrayHelper::map(
                     \Yii::$app->registeredModels->getDescriptor($model)->getTypes()->getComponents(),
                     "id",
                     "name"
                 ),
        ])->hint('От выбранного типа публикации может зависеть, то, как она будет отображаться.');
    ?>

    <?= $form->field($model, 'tree_id')->label('Главный раздел сайта')->widget(
        \skeeks\widget\chosen\Chosen::className(), [
            'items' => \skeeks\cms\models\helpers\Tree::getAllMultiOptions()
        ])->hint('Главный раздел сайта, к которму привязана публикация');
    ?>


    <?= $form->field($model, 'tree_ids')->label('Дополнительные разделы сайта')->widget(
        \skeeks\cms\widgets\formInputs\selectTree\SelectTree::className(),
        [

        ])->hint('Дополнительные разделы сайта, где бы хотелось видеть эту публикацию');
    ?>

    <?= $form->buttonsCreateOrUpdate($model); ?>


<?php ActiveForm::end(); ?>




