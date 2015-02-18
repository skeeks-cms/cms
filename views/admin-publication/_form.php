<?php

use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\ActiveForm;
use skeeks\cms\models\Tree;

/* @var $this yii\web\View */
/* @var $model Tree */
?>

<?php \yii\widgets\Pjax::begin([
    'id' => 'my-pjax',
]); ?>

    <?php $form = ActiveForm::begin([
        'options' => ['data-pjax' => true]
]); ?>

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
<? if ($notify): ?>
    <?  $type = $notify[0];?>
    <?  $message = $notify[1];?>
    <? \Yii::$app->view->registerJs(<<<JS
        (function(sx, $, _)
        {
            sx.notify.$type("$message");
        })(sx, sx.$, sx._);
JS
    );
    ?>
<? endif; ?>

<? \Yii::$app->view->registerJs(<<<JS
        (function(sx, $, _)
        {
            var blockerPanel = new sx.classes.Blocker('.sx-panel');

            $(document).on('pjax:send', function(e)
            {
                var blockerPanel = new sx.classes.Blocker($(e.target));
                blockerPanel.block();
            })

            $(document).on('pjax:complete', function(e) {
                blockerPanel.unblock();
            })

        })(sx, sx.$, sx._);
JS
    );
    ?>

<?php \yii\widgets\Pjax::end(); ?>