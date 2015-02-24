<?php
/**
 * login
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 18.02.2015
 * @since 1.0.0
 */
/* @var $this \yii\web\View */
use yii\helpers\Html;
use \skeeks\cms\modules\admin\widgets\ActiveForm;
$urlBg = \Yii::$app->assetManager->getAssetUrl(\skeeks\cms\modules\admin\assets\AdminAsset::register($this), 'images/bg/582738_www.Gde-Fon.com.jpg');

$this->registerCss(<<<CSS
    body
    {
        background: silver center fixed;
    }
    body.sx-styled
    {
        background: url({$urlBg}) center fixed;
    }

    .navbar.op-05:hover, .sx-admin-footer.op-05:hover
    {
        opacity: 1;
        transition-duration: 1s;
    }

    .navbar, .sx-admin-footer {
        display: none;
    }
    .navbar.op-05, .sx-admin-footer.op-05 {
        opacity: 0.5;
    }


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

    .sx-hidden
    {
        display: none;
    }

    form.sx-form-admin
    {
        border: none;
        padding: 0px;
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
                var self = this;
                this.blockerHtml = sx.block('html', {
                    message: "<div style='padding: 10px;'><h2>Загрузка...</h2></div>",
                    css: {
                        "border-radius": "6px",
                        "border-width": "3px",
                        "border-color": "rgba(32, 168, 216, 0.25)",
                        "box-shadow": "0 11px 51px 9px rgba(0,0,0,.55)"
                    }
                });

                this.blockerLogin = new sx.classes.Blocker('.sx-panel', {
                    message: "<div style='padding: 10px;'><h2>Загрузка...</h2></div>",
                    css: {
                        "border-radius": "6px",
                        "border-width": "1px",
                        "border-color": "rgba(32, 168, 216, 0.25)",
                        "box-shadow": "0 11px 51px 9px rgba(0,0,0,.55)"
                    }
                });

                this.JloginContainer = $('.sx-act-login');
                this.JSuccessLoginContainer = $('.sx-act-successLogin');
                this.JForgetContainer = $('.sx-act-forget');
            },

            _onWindowReady: function()
            {
                var self = this;
                $("body").addClass('sx-styled');

                this.blockerHtml.unblock();

                _.delay(function()
                {
                    $('.sx-auth').fadeIn();
                }, 500);

                _.delay(function()
                {
                    self.JloginContainer.fadeIn();
                }, 500);

                _.delay(function()
                {
                    $('.navbar, .sx-admin-footer').addClass('op-05').fadeIn();
                }, 1000);
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

        sx.auth = new sx.classes.Auth({
            'bg': '{$urlBg}'
        });
    })(sx, sx.$, sx._);
JS
);
?>
<div style="display: none;">
    <img src="<?= $urlBg; ?>" id="sx-auth-bg"/></div>
</div>
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
                            <?= $form->field($model, 'username')->label('Логин'); ?>
                            <?= $form->field($model, 'password')->passwordInput()->label('Пароль') ?>
                                <div class="form-group sx-submit-group">
                                    <?= Html::submitButton("<i class='glyphicon glyphicon-off'></i> Войти", ['class' => 'btn btn-primary', 'name' => 'login-button']) ?>
                                </div>

                                <div class="sx-hidden1">
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
                            <?= $form->field($model, 'username')->label('Логин'); ?>
                            <p style="text-align: center;">Или</p>
                            <?= $form->field($model, 'username')->label('Логин'); ?>
                                <div class="form-group sx-submit-group">
                                    <?= Html::submitButton("<i class='glyphicon glyphicon-off'></i> Восстановить пароль", [
                                        'class' => 'btn btn-primary',
                                        'name' => 'login-button',
                                        'onclick' => 'sx.notify.info("Не нажимайте пока меня, я еще не работаю )"); return false;'
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

