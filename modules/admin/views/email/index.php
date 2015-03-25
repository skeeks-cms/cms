
<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 30.01.2015
 * @since 1.0.0
 */
/* @var $this yii\web\View */
/* @var $model \skeeks\cms\modules\admin\models\forms\SshConsoleForm */
use skeeks\cms\modules\admin\widgets\ActiveForm;
use \yii\helpers\Html;

use Yii;
?>

<div class="sx-widget-ssh-console">
    <? $form = ActiveForm::begin([
        'usePjax' => false
    ]) ?>

        <?= $form->field($model, 'to')->textInput([
            'placeholder' => 'email',

        ]); ?>

        <?= $form->field($model, 'from')->textInput([
            'placeholder' => 'email',
            'value' => \Yii::$app->params['adminEmail']
        ]); ?>

        <?= $form->field($model, 'subject')->textInput([
            'placeholder' => 'Тема',
            'value' => 'Тестовое письмо'
        ]); ?>

        <?= $form->field($model, 'content')->textarea([
            'placeholder' => 'Тело сообщения',
            'value' => 'Тестовое письмо'
        ]); ?>

        <?= Html::tag('div',
            Html::submitButton("Отправить email", ['class' => 'btn btn-primary']),
            ['class' => 'form-group']
        ); ?>

        <div class="sx-result-container">
            <pre id="sx-result">
<?= $result; ?>
            </pre>
        </div>
    <? ActiveForm::end() ?>
</div>

