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
        sx.classes.Blocked = sx.classes.Component.extend({
        
            _init: function()
            {},

            _onDomReady: function()
            {
                _.delay(function()
                {
                    $("[type=password]").val('');
                }, 200);
            },
            
            afterValidate: function(jForm, ajaxQuery)
            {
                var handler = new sx.classes.AjaxHandlerStandartRespose(ajaxQuery, {
                    'blocker'                           : sx.AppUnAuthorized.PanelBlocker,
                    'blockerSelector'                   : '',
                    'enableBlocker'                     : true,
                    'redirectDelay'                     : 2000,
                    'allowResponseSuccessMessage'       : false,
                    'allowResponseErrorMessage'         : false,
                });

                handler.bind('success', function(e, data)
                {
                    _.delay(function()
                    {
                        sx.AppUnAuthorized.triggerBeforeReddirect();
                    }, 200)
                });
            }
        });

        sx.Blocked = new sx.classes.Blocked();
    })(sx, sx.$, sx._);
JS
);

$logoutUrl = \skeeks\cms\helpers\UrlHelper::construct("admin/auth/logout")->enableAdmin()->setCurrentRef();
?>

<div class="main sx-content-block sx-windowReady-fadeIn">
    <div class="col-lg-4"></div>

    <div class="col-lg-4">
        <div class="panel panel-primary sx-panel">
            <div class="panel-body">
                <div class="panel-content">
                        <?php $form = ActiveForm::begin([
                            'id'                            => 'blocked-form',
                            'validationUrl'                 => (string) \skeeks\cms\helpers\UrlHelper::constructCurrent()->enableAjaxValidateForm(),
                            'afterValidateCallback'         => 'sx.Blocked.afterValidate',
                        ]); ?>

                                <div class="row">
                                    <div class="col-lg-3">
                                        <img src="<?= \skeeks\cms\helpers\Image::getSrc(\Yii::$app->user->identity->getMainImageSrc()); ?>" style="width: 100%;"/>
                                    </div>
                                    <div class="col-lg-9">
                                        <?= $form->field($model, 'password')->passwordInput([
                                            'placeholder' => 'Пароль',
                                            'autocomplete' => 'off',
                                        ])->label(\Yii::$app->user->identity->displayName) ?>
                                        <?= Html::submitButton("<i class='glyphicon glyphicon-lock'></i> Разблокировать", ['class' => 'btn btn-primary', 'name' => 'login-button']) ?>

                                    </div>
                                </div>
                        <?php ActiveForm::end(); ?>
                                <div>
                                    <hr />
                                    <div style="color:#999;margin:1em 0">
                                        Вы успешно авторизованы, но слишком долго не проявляли активность в панеле управления сайтом.
                                        Пожалуйста, подтвердите что это вы, и введите ваш пароль.
                                        <p>

                                            <?= Html::a('<i class="glyphicon glyphicon-off"></i> Выход', $logoutUrl, [
                                                "data-method" => "post",
                                                "data-pjax" => "0",
                                                "class" => "btn btn-danger btn-xs pull-right",
                                            ]); ?>
                                        </p>
                                    </div>
                                </div>


                </div>
            </div>
        </div>

    </div><!-- End .col-lg-12  -->

    <div class="col-lg-4"></div>
</div>

