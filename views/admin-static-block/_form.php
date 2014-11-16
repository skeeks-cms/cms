<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use skeeks\cms\models\Tree;

/* @var $this yii\web\View */
/* @var $model Tree */
/* @var $form yii\widgets\ActiveForm */
?>

<? $this->registerJs(<<<JS
(function(sx, $, _)
{
    sx.createNamespace('classes.app', sx);

    sx.classes.app.SelectSite = sx.classes.Widget.extend({

        _init: function()
        {

        },

        _onDomReady: function()
        {
            var self = this;
            this._JForm = this.getWrapper();
            this._JSelectSite = $('select', this.getWrapper());

            this._JSelectSite.on('change', function()
            {
                self.getWrapper().submit();
            });
        },

        _onWindowReady: function()
        {}
    });

    new sx.classes.app.SelectSite('#select-site');
})(sx, sx.$, sx._);
JS
) ?>

<form id="select-site">
    <?=
        \skeeks\widget\chosen\Chosen::widget([
            'name' => 'section',
            'value' => \Yii::$app->request->get('section'),
            'items' =>
                \yii\helpers\ArrayHelper::map(
                 \skeeks\cms\models\Site::getAll(),
                 "id",
                 "host_name"
             ),
        ]);
    ?>
    <input type="hidden" name="id" value="<?= $model->id; ?>" />
</form>
<hr />

<?php $form = ActiveForm::begin(); ?>

<?= $form->field($model, 'code')->textInput(['maxlength' => 255]) ?>
<?= $form->field($model, 'description')->textarea() ?>
<?= $form->field($model, 'value')->textarea() ?>




<div class="form-group">
    <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
</div>
<?php ActiveForm::end(); ?>