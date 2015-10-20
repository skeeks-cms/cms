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
//use \skeeks\cms\modules\admin\widgets\ActiveForm;

use \skeeks\cms\base\widgets\ActiveFormAjaxSubmit as ActiveForm;

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
                this.JloginContainer        = $('.sx-act-login');
                this.JSuccessLoginContainer = $('.sx-act-successLogin');
                this.JForgetContainer       = $('.sx-act-forget');
            },

            _onWindowReady: function()
            {
                var self = this;

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


            afterValidateLogin: function(jForm, ajaxQuery)
            {
                var handler = new sx.classes.AjaxHandlerStandartRespose(ajaxQuery, {
                    'blocker'                           : sx.AppUnAuthorized.PanelBlocker,
                    'blockerSelector'                   : '',
                    'enableBlocker'                     : true,
                    'redirectDelay'                     : 500,
                    'allowResponseSuccessMessage'       : false,
                    'allowResponseErrorMessage'         : false,
                });

                new sx.classes.AjaxHandlerNoLoader(ajaxQuery);

                handler.bind('success', function(e, response)
                {
                    if (response.message)
                    {
                        $('.sx-form-messages', jForm).empty().append(
                            $('<div>',{
                                'class' : 'alert alert-success',
                                'data-dismiss' : 'alert',
                                'aria-label' : 'Закрыть',
                            })
                            .append('<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>')
                            .append(response.message)
                        );
                    }

                    _.delay(function()
                    {
                        sx.AppUnAuthorized.triggerBeforeReddirect();
                    }, 200)
                });

                handler.bind('error', function(e, response)
                {
                    if (response.message)
                    {
                        $('.sx-form-messages', jForm).empty().append(
                            $('<div>',{
                                'class' : 'alert alert-danger',
                                'data-dismiss' : 'alert',
                                'aria-label' : 'Закрыть',
                            })
                            .append('<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>')
                            .append(response.message)
                        );
                    }

                });
            },

            afterValidateResetPassword: function(jForm, ajaxQuery)
            {
                var self = this;

                $('.sx-form-messages', jForm).empty();

                var handler = new sx.classes.AjaxHandlerStandartRespose(ajaxQuery, {
                    'blocker'                           : sx.AppUnAuthorized.PanelBlocker,
                    'blockerSelector'                   : '',
                    'enableBlocker'                     : true,
                    'redirectDelay'                     : 2000,
                    'allowResponseSuccessMessage'       : false,
                    'allowResponseErrorMessage'         : false,
                });

                new sx.classes.AjaxHandlerNoLoader(ajaxQuery);

                handler.bind('success', function(e, response)
                {
                    if (response.message)
                    {
                        $('.sx-form-messages', jForm).empty().append(
                            $('<div>',{
                                'class' : 'alert alert-success',
                                'data-dismiss' : 'alert',
                                'aria-label' : 'Закрыть',
                            })
                            .append('<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>')
                            .append(response.message)
                        );
                    }

                    _.delay(function()
                    {
                        self.goActLogin();
                    }, 2000);

                });

                handler.bind('error', function(e, response)
                {
                    if (response.message)
                    {
                        $('.sx-form-messages', jForm).empty().append(
                            $('<div>',{
                                'class' : 'alert alert-danger',
                                'data-dismiss' : 'alert',
                                'aria-label' : 'Закрыть',
                            })
                            .append('<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>')
                            .append(response.message)
                        );
                    }

                });
            },

        });

        sx.auth = new sx.classes.Auth({});
    })(sx, sx.$, sx._);
JS
);
?>

<div class="main sx-auth sx-content-block sx-windowReady-fadeIn">
    <div class="col-lg-4"></div>

    <div class="col-lg-4">
        <div class="panel panel-primary sx-panel">
            <div class="panel-body">
                <div class="panel-content">

                    <div class="sx-act sx-act-login">
                        <?php $form = ActiveForm::begin([
                            'id'                            => 'login-form',
                            'enableAjaxValidation'          => false,
                            'afterValidateCallback'         => 'function(jForm, ajaxQuery){ sx.auth.afterValidateLogin(jForm, ajaxQuery); }',
                        ]); ?>

                            <div class="sx-form-messages"></div>

                            <?= $form->field($loginModel, 'identifier')->label(\Yii::t('app','Username or Email')); ?>
                            <?= $form->field($loginModel, 'password')->passwordInput()->label(\Yii::t('app','Password')) ?>
                                <?= Html::input('hidden', 'do', 'login'); ?>
                                <div class="form-group sx-submit-group">
                                    <?= Html::submitButton("<i class='glyphicon glyphicon-off'></i> " . \Yii::t('app','Log in'), ['class' => 'btn btn-primary', 'name' => 'login-button']) ?>
                                </div>

                                <div>
                                    <hr />
                                    <div style="color:#999;margin:1em 0">
                                        <a href="#" class="sx-act-controll" onclick="sx.auth.goActForget(); return false;"><?= \Yii::t('app','recover password')?></a>
                                    </div>
                                </div>
                        <?php ActiveForm::end(); ?>
                    </div>

                    <div class="sx-act sx-act-forget">
                        <?php $form = ActiveForm::begin([
                            'id' => 'forget-form',
                            'afterValidateCallback'         => 'function(jForm, ajaxQuery){ sx.auth.afterValidateResetPassword(jForm, ajaxQuery); }',
                        ]); ?>

                            <div class="sx-form-messages"></div>

                            <?= $form->field($passwordResetModel, 'identifier')->label(\Yii::t('app','Username or Email')); ?>
                                <?= Html::input('hidden', 'do', 'password-reset'); ?>
                                <div class="form-group sx-submit-group">
                                    <?= Html::submitButton("<i class='glyphicon glyphicon-off'></i> " . \Yii::t('app','Recover password'), [
                                        'class' => 'btn btn-primary',
                                        'name' => 'login-button',
                                        //'onclick' => 'sx.notify.info("Не нажимайте пока меня, я еще не работаю )"); return false;'
                                    ]) ?>
                                </div>

                                <div class="sx-hidden1">
                                    <hr />
                                    <div style="color:#999;margin:1em 0">
                                        <?= \Yii::t('app','I remembered password')?> <a href="#" class="sx-act-controll" onclick="sx.auth.goActLogin(); return false;"><?= \Yii::t('app','log in') ?></a>
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

