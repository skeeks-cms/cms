
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

$authLink = \skeeks\cms\helpers\UrlHelper::construct('admin/index')->enableAbsolute()->enableAdmin();

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
                    window.location.replace('$authLink')
                }, 5000);
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

                    <div class="sx-act-reset-password">
                        <?=$message?>
                    </div>

                </div>
            </div>
        </div>

    </div><!-- End .col-lg-12  -->

    <div class="col-lg-4"></div>
</div>

