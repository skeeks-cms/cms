
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
?>

<div class="sx-widget-ssh-console">
    <? $form = ActiveForm::begin([
        'usePjax' => true
    ]) ?>

        <?= $form->field($model, 'to')->textInput([
            'placeholder'   => 'email',
            'value'         => \Yii::$app->user->identity->email,
        ]); ?>

        <?= $form->field($model, 'from')->textInput([
            'placeholder' => 'email',
            'value' => \Yii::$app->cms->adminEmail
        ]); ?>

        <?= $form->field($model, 'subject')->textInput([
            'placeholder' => \Yii::t('app','Subject'),
            'value' => \Yii::t('app','Letter test')
        ]); ?>

        <?= $form->field($model, 'content')->textarea([
            'placeholder' => 'Тело сообщения',
            'value' => \Yii::t('app','Letter test'),
            'rows' => 8
        ]); ?>

        <?= Html::tag('div',
            Html::submitButton(\Yii::t('app',"Send {email}",['email' => "email"]), ['class' => 'btn btn-primary']),
            ['class' => 'form-group']
        ); ?>

        <? if ($result) : ?>
            <h2><?=\Yii::t('app','Result of sending')?>: </h2>
                    <div class="sx-result-container">
                        <pre id="sx-result">
<p><?= $result; ?></p>
                        </pre>
                    </div>
        <? endif; ?>



    <h2><?= \Yii::t('app','Configuration of component {cms} sending {email}',['cms' => 'cms', 'email' => 'email'])?>: </h2>
    <div class="sx-result-config">
        <pre id="sx-result">
<p><?= \Yii::t('app','Mail component')?>: <?= \Yii::$app->mailer->className(); ?></p>
<p><?= \Yii::t('app','Transport')?>: <?= (new \ReflectionObject(\Yii::$app->mailer->transport))->getName(); ?></p>
<p><?= \Yii::t('app','Transport running')?>: <?= (int) \Yii::$app->mailer->transport->isStarted(); ?></p>
<p><?= \Yii::t('app','Mailer viewPath')?>: <?= \Yii::$app->mailer->viewPath; ?></p>
<p><?= \Yii::t('app','Mailer messageClass')?>: <?= \Yii::$app->mailer->messageClass; ?></p>
        </pre>
    </div>


    <h2><?=\Yii::t('app','Configuration of {php} sending {email}',['php' => 'php', 'email' => 'email'])?>: </h2>
    <div class="sx-result-config">
        <pre id="sx-result">
<p><?= \Yii::t('app','Sendmail Path')?>: <?= ini_get('sendmail_path') ?></p>
<p><?= \Yii::t('app','Sendmail From')?>: <?= ini_get('sendmail_from') ?></p>
        </pre>
    </div>
    <? ActiveForm::end() ?>
</div>

