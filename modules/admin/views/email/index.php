
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

use \Yii;
?>

<div class="sx-widget-ssh-console">
    <? $form = ActiveForm::begin([
        'usePjax' => true
    ]) ?>

        <?= $form->field($model, 'to')->textInput([
            'placeholder'   => 'email',
            'value'         => \Yii::$app->cms->getAuthUser()->email,
        ]); ?>

        <?= $form->field($model, 'from')->textInput([
            'placeholder' => 'email',
            'value' => \Yii::$app->cms->adminEmail
        ]); ?>

        <?= $form->field($model, 'subject')->textInput([
            'placeholder' => 'Тема',
            'value' => 'Тестовое письмо'
        ]); ?>

        <?= $form->field($model, 'content')->textarea([
            'placeholder' => 'Тело сообщения',
            'value' => 'Тестовое письмо',
            'rows' => 8
        ]); ?>

        <?= Html::tag('div',
            Html::submitButton("Отправить email", ['class' => 'btn btn-primary']),
            ['class' => 'form-group']
        ); ?>

        <? if ($result) : ?>
            <h2>Результат отправки: </h2>
                    <div class="sx-result-container">
                        <pre id="sx-result">
<p><?= $result; ?></p>
                        </pre>
                    </div>
        <? endif; ?>



    <h2>Конфигурация cms компонента отправки email: </h2>
    <div class="sx-result-config">
        <pre id="sx-result">
<p>Mail component: <?= \Yii::$app->mailer->className(); ?></p>
<p>Транспорт: <?= (new \ReflectionObject(\Yii::$app->mailer->transport))->getName(); ?></p>
<p>Транспорт запущен: <?= (int) \Yii::$app->mailer->transport->isStarted(); ?></p>
<p>Mailer viewPath: <?= \Yii::$app->mailer->viewPath; ?></p>
<p>Mailer messageClass: <?= \Yii::$app->mailer->messageClass; ?></p>
        </pre>
    </div>


    <h2>Конфигурация php отправки email: </h2>
    <div class="sx-result-config">
        <pre id="sx-result">
<p>Sendmail Path: <?= ini_get('sendmail_path') ?></p>
<p>Sendmail From: <?= ini_get('sendmail_from') ?></p>
        </pre>
    </div>
    <? ActiveForm::end() ?>
</div>

