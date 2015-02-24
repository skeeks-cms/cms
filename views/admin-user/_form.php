<?php

use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\form\ActiveFormStyled as ActiveForm;
use common\models\User;

/* @var $this yii\web\View */
/* @var $model \yii\db\ActiveRecord */
/* @var $console \skeeks\cms\controllers\AdminUserController */
?>


<?php $form = ActiveForm::begin(); ?>
<?php  ?>

<?= $form->fieldSet('Общая ниформация')?>
    <?= $form->field($model, 'username')->textInput(['maxlength' => 12]) ?>
<? if (!$model->isNewRecord) : ?>
    <?= $form->field($model, 'name')->textInput(); ?>
    <?= $form->field($model, 'city')->textInput(); ?>
    <?= $form->field($model, 'address')->textInput(); ?>
    <?= $form->field($model, 'info')->textarea(); ?>
    <?= $form->field($model, 'status_of_life')->textarea(); ?>
<? endif; ?>
<?= $form->fieldSetEnd(); ?>
<?= $form->buttonsCreateOrUpdate($model); ?>
<?php ActiveForm::end(); ?>

<? if (!$model->isNewRecord) : ?>
    <?
        $action = \skeeks\cms\helpers\UrlHelper::construct('cms/admin-user-email/create', ['user_id' => $model->id])->enableAdmin()->toString();
        $this->registerJs(<<<JS
        (function(sx, $, _)
        {
            sx.classes.CreateUserEmail = sx.classes.Component.extend({

                _init: function()
                {
                    var window = new sx.classes.Window(this.get('action'));
                    window.bind("close", function()
                    {
                        sx.notify.info('ready');
                    });

                    window.open();
                },

                _onDomReady: function()
                {},

                _onWindowReady: function()
                {},
            });
        })(sx, sx.$, sx._);
JS
);
    ?>


    <a class="btn btn-default" onclick="<?= new \yii\web\JsExpression(<<<JS
    new sx.classes.CreateUserEmail({'action': '{$action}'}); return false;
JS
); ?>">Добавить</a>
        <?
            $search = new \skeeks\cms\models\Search(\skeeks\cms\models\user\UserEmail::className());
            $search->getDataProvider()->query->where(['user_id' => $model->id]);
        ?>
        <?= \skeeks\cms\modules\admin\widgets\GridView::widget([
            'dataProvider'  => $search->getDataProvider(),
            'layout' => "\n{items}\n{pager}",
            'columns' => [
                //['class' => 'yii\grid\SerialColumn'],

                [
                    'class'         => \skeeks\cms\modules\admin\grid\ActionColumn::className(),
                    'controller'    => \Yii::$app->createController('cms/admin-user-email')[0],
                    'isOpenNewWindow'    => true
                ],

                'value',
                'approved',
            ],
        ]); ?>

<? endif; ?>


