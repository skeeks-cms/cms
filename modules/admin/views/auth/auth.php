<?php
/**
 * auth
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 26.02.2015
 */
/* @var $this \yii\web\View */
use yii\helpers\Html;
use \skeeks\cms\modules\admin\widgets\ActiveForm;

$this->registerCss(<<<CSS
.sx-auth
{
    display: none;
}
    .sx-auth .sx-panel
    {
        margin-top: 20%;
        border-radius: 6px;
        border-width: 3px;
        border-color: rgba(32, 168, 216, 0.25);
        box-shadow: 0 11px 51px 9px rgba(0,0,0,.55);
        padding: 10px;
    }

    .sx-auth form input
    {
        height: 45px;
        font-size: 25px;
        border-radius: 6px;
        border-width: 2px;
    }
    .sx-auth form button
    {
        font-size: 20px;
    }
    .sx-auth form label
    {
        font-size: 16px;
    }

    .sx-auth form .sx-submit-group
    {
        text-align: center;
    }

    .sx-act
    {
        display: none;
    }

    .sx-act-controll
    {
        border-bottom: 1px dashed;
        text-decoration: none;
    }

    .sx-act-controll:hover
    {
        border-bottom: 1px dashed;
        text-decoration: none;
    }
CSS
);
$this->registerJs(<<<JS
    (function(sx, $, _)
    {
        sx.createNamespace('classes', sx);

        sx.classes.Auth = sx.classes.Component.extend({

            _init: function()
            {
                this.loader = new sx.classes.AjaxLoader();
                this.blocker = new sx.classes.Blocker();
            },

            _onDomReady: function()
            {
                this.JloginContainer = $('.sx-act-login');
                this.JSuccessLoginContainer = $('.sx-act-successLogin');
                this.JForgetContainer = $('.sx-act-forget');
            },

            _onWindowReady: function()
            {
                var self = this;

                _.delay(function()
                {
                    $('.sx-auth').fadeIn();
                }, 500);

                _.delay(function()
                {
                    self.JloginContainer.fadeIn();
                }, 500);
            },

            closeAllActs: function()
            {
                $(".sx-act").fadeOut();
                return this;
            },

            goActLogin: function()
            {
                var self = this;
                $(".sx-act:visible").slideUp(200, function()
                {
                    self.JloginContainer.slideDown(500);
                });
                return this;
            },

            goActForget: function()
            {
                var self = this;
                $(".sx-act:visible").slideUp(200, function()
                {
                    self.JForgetContainer.slideDown(500);
                });
                return this;
            },

            goActSuccessLogin: function()
            {
                var self = this;
                $(".sx-act:visible").fadeOut(500, function()
                {
                    self.JSuccessLoginContainer.fadeIn(500);
                });
                return this;
            },

            loginnedSuccess: function(urlGo)
            {
                var self = this;

                _.delay(function()
                {
                    $(".navbar").slideUp(800);
                    $(".sx-admin-footer").slideUp(800);
                }, 300);

                _.delay(function()
                {
                    self.goActSuccessLogin();
                }, 300);

                _.delay(function()
                {
                    window.location.href = urlGo;
                }, 3000);

            }
        });

        sx.auth = new sx.classes.Auth({});
    })(sx, sx.$, sx._);
JS
);
?>

<div class="main sx-auth">
    <div class="col-lg-4"></div>

    <div class="col-lg-4">
        <div class="panel panel-primary sx-panel">
            <div class="panel-body">
                <div class="panel-content">

                    <div class="sx-act sx-act-login">
                        <?php $form = ActiveForm::begin([
                            'id' => 'login-form',
                            'enableAjaxValidation' => false,
                            'PjaxOptions' =>
                            [
                                'blockPjaxContainer'   => false,
                                'blockContainer'        => '.sx-panel'
                            ],
                        ]); ?>
                            <? if (\Yii::$app->request->isAjax && $success) : ?>
                                <? $this->registerJs(<<<JS
                                (function(sx, $, _)
                                {
                                    sx.auth.loginnedSuccess('{$goUrl}');
                                })(sx, sx.$, sx._);
JS
)?>
                            <? endif;?>
                            <?= $form->field($loginModel, 'identifier')->label('Логин или email'); ?>
                            <?= $form->field($loginModel, 'password')->passwordInput()->label('Пароль') ?>
                                <?= Html::input('hidden', 'do', 'login'); ?>
                                <div class="form-group sx-submit-group">
                                    <?= Html::submitButton("<i class='glyphicon glyphicon-off'></i> Войти", ['class' => 'btn btn-primary', 'name' => 'login-button']) ?>
                                </div>

                                <div>
                                    <hr />
                                    <div style="color:#999;margin:1em 0">
                                        Данная опция пока не работает, но она будет тут: <a href="#" class="sx-act-controll" onclick="sx.auth.goActForget(); return false;">восстановить пароль</a>
                                    </div>
                                </div>
                        <?php ActiveForm::end(); ?>
                    </div>

                    <div class="sx-act sx-act-successLogin">
                        <p class="sx-step sx-step-1">Авторизация прошла успешно</p>
                        <p class="sx-step sx-step-2">Ожидайте сейчас все будет...</p>
                    </div>

                    <div class="sx-act sx-act-forget">
                        <?php $form = ActiveForm::begin([
                            'id' => 'forget-form',
                            'PjaxOptions' =>
                            [
                                'blockPjaxContainer'   => false,
                                'blockContainer'        => '.sx-panel-login'
                            ],
                        ]); ?>
                            <? if ($successReset === true) : ?>
                                <div class="alert alert-success" data-dismiss="alert" aria-label="Close"><?= $resetMessage; ?></div>
                            <? elseif($successReset === false) : ?>
                                <div class="alert alert-danger" data-dismiss="alert" aria-label="Close"><?= $resetMessage; ?></div>
                            <? endif; ?>

                            <?= $form->field($passwordResetModel, 'identifier')->label('Логин или email'); ?>
                                <?= Html::input('hidden', 'do', 'password-reset'); ?>
                                <div class="form-group sx-submit-group">
                                    <?= Html::submitButton("<i class='glyphicon glyphicon-off'></i> Восстановить пароль", [
                                        'class' => 'btn btn-primary',
                                        'name' => 'login-button',
                                        //'onclick' => 'sx.notify.info("Не нажимайте пока меня, я еще не работаю )"); return false;'
                                    ]) ?>
                                </div>

                                <div class="sx-hidden1">
                                    <hr />
                                    <div style="color:#999;margin:1em 0">
                                        Я вспомнил пароль <a href="#" class="sx-act-controll" onclick="sx.auth.goActLogin(); return false;">авторизоваться</a>
                                    </div>
                                </div>

                        <?php ActiveForm::end(); ?>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- End .col-lg-12  -->

    <div class="col-lg-4"></div>
</div>

