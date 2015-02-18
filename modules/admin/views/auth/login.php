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
                this.blocker = sx.block('html', {
                    message: "<div style='padding: 10px;'><h2>Загрузка...</h2></div>",
                    css: {
                        "border-radius": "6px",
                        "border-width": "3px",
                        "border-color": "rgba(32, 168, 216, 0.25)",
                        "box-shadow": "0 11px 51px 9px rgba(0,0,0,.55)"
                    }
                });

                this.blockerPanel = new sx.classes.Blocker('.sx-panel', {
                    message: "<div style='padding: 10px;'><h2>Загрузка...</h2></div>",
                    css: {
                        "border-radius": "6px",
                        "border-width": "1px",
                        "border-color": "rgba(32, 168, 216, 0.25)",
                        "box-shadow": "0 11px 51px 9px rgba(0,0,0,.55)"
                    }
                });

                //TODO: плохое решение, временное.
                $(document)
                    .bind(sx.ajax.ajaxStart, function(e, data){
                        self.blockerPanel.block();
                    })
                    .bind(sx.ajax.ajaxStop, function(e, data){
                        self.blockerPanel.unblock();
                    })
                ;
            },

            _onWindowReady: function()
            {
                $("body").addClass('sx-styled');

                this.blocker.unblock();

                _.delay(function()
                {
                    $('.sx-auth').fadeIn();
                }, 500);

                _.delay(function()
                {
                    $('.navbar, .sx-admin-footer').addClass('op-05').fadeIn();
                }, 1000);
            }
        });

        new sx.classes.Auth({
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

                    <div class="site-login" style="padding: 20px;">
                        <div class="row">
                            <div class="col-lg-12">
                                <?php \yii\widgets\Pjax::begin(['id' => 'my-pjax']); ?>
                                    <?php $form = ActiveForm::begin([
                                        'id' => 'login-form',
                                        'options' => ['data-pjax' => true]
                                    ]); ?>
                                        <?= $form->field($model, 'username')->label('Логин'); ?>
                                        <?= $form->field($model, 'password')->passwordInput()->label('Пароль') ?>

                                        <div class="form-group sx-submit-group">
                                            <?= Html::submitButton("<i class='glyphicon glyphicon-off'></i> Войти", ['class' => 'btn btn-primary', 'name' => 'login-button']) ?>
                                        </div>

                                        <div class="sx-hidden">
                                            <hr />
                                            <div style="color:#999;margin:1em 0">
                                                Если вы забыли пароль, обратитесь к администратору сайта, или разработчикам. В целях безопастности, мы не даем возможности автоматического восстановления пароля.
                                            </div>
                                        </div>
                                    <?php ActiveForm::end(); ?>
                                <?php \yii\widgets\Pjax::end(); ?>
                            </div>

                            <!--Или социальные сети
                            --><?/*= yii\authclient\widgets\AuthChoice::widget([
                                 'baseAuthUrl' => ['site/auth']
                            ]) */?>
                        </div>
                    </div>


                </div><!-- End .panel-body -->
            </div><!-- End .panel-body -->
        </div><!-- End .widget -->

    </div><!-- End .col-lg-12  -->

    <div class="col-lg-4"></div>
</div>

