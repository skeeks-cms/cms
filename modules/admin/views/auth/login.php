<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

?>
<div class="site-login" style="padding: 20px;">
    <div class="row">
        <div class="col-lg-5">
            <?php $form = ActiveForm::begin(['id' => 'login-form']); ?>
                <?= $form->field($model, 'username') ?>
                <?= $form->field($model, 'password')->passwordInput() ?>
                <?= $form->field($model, 'rememberMe')->checkbox() ?>

                <div class="form-group">
                    <?= Html::submitButton("Войти", ['class' => 'btn btn-primary', 'name' => 'login-button']) ?>
                </div>
                <hr />
                <div style="color:#999;margin:1em 0">
                    Если вы забыли пароль, обратитесь к администратору сайта, или разработчикам. В целях безопастности, мы не даем возможности автоматического восстановления пароля.
                </div>
            <?php ActiveForm::end(); ?>
        </div>

        <!--Или социальные сети
        --><?/*= yii\authclient\widgets\AuthChoice::widget([
             'baseAuthUrl' => ['site/auth']
        ]) */?>
    </div>
</div>