<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 05.03.2015
 */
?>

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



$this->registerJs(<<<JS
    (function(sx, $, _)
    {
        sx.createNamespace('classes', sx);

        sx.classes.Auth = sx.classes.Component.extend({

            _onWindowReady: function()
            {
                var self = this;

                _.delay(function()
                {
                    $('.sx-auth').fadeIn();
                }, 500);

            },
        });

        sx.classes.ConnectDb = sx.classes.Component.extend({

            _init: function()
            {},

            _onDomReady: function()
            {
                $(".sx-btn-additional").on("click", function()
                {
                    if ($(".sx-additional").hasClass('sx-hide'))
                    {
                        $(".sx-additional").removeClass('sx-hide');
                    } else
                    {
                        $(".sx-additional").addClass('sx-hide');
                    }
                });
            },

            _onWindowReady: function()
            {}
        });

        new sx.classes.ConnectDb();

        sx.auth = new sx.classes.Auth({});
    })(sx, sx.$, sx._);
JS
);
?>

<div class="main sx-content-block sx-windowReady-fadeIn">
    <div class="col-lg-4"></div>

    <div class="col-lg-4">
        <div class="panel panel-primary sx-panel">
            <div class="panel-body">
                <div class="panel-content">

                    <div class="sx-act-reset-password">
                        <div class="alert alert-danger" role="alert">
                            Настройте подключение к базе данных!
                        </div>

                        <div class="alert alert-info" role="alert">
                            Путь к файлу настроек: <?= \Yii::getAlias('@common/config/db.php'); ?>
                        </div>

                        <? if (YII_ENV != "prod") : ?>
                            <? $form = \skeeks\cms\base\widgets\ActiveFormAjaxSubmit::begin([
                                'enableAjaxValidation'         => false,
                                'afterValidateCallback'        => new \yii\web\JsExpression(<<<JS
    function(Jform, ajax)
    {
        ajax.bind('success', function()
        {
            window.location.reload();
        });
    }
JS
)
                            ]); ?>
                                <?= $form->field($connectToDbForm, 'host')->textInput(); ?>
                                <?= $form->field($connectToDbForm, 'dbname')->textInput(); ?>
                                <?= $form->field($connectToDbForm, 'username')->textInput(); ?>
                                <?= $form->field($connectToDbForm, 'password')->passwordInput(); ?>
                                <a href="#" class="sx-btn-additional">Дополнительные настройки</a>
                                <div class="sx-additional sx-hide">
                                    <?= $form->field($connectToDbForm, 'charset')->textInput(); ?>
                                    <?= $form->fieldRadioListBoolean($connectToDbForm, 'enableSchemaCache'); ?>
                                    <?= $form->fieldInputInt($connectToDbForm, 'schemaCacheDuration'); ?>
                                </div>
                                <div class="form-group sx-buttons-standart">
                                    <button type="submit" class="btn btn-success">
                                        <i class="glyphicon glyphicon-save"></i> Сохранить и продолжить
                                    </button>
                                </div>
                            <? \skeeks\cms\base\widgets\ActiveFormAjaxSubmit::end(); ?>
                        <? endif; ?>

                    </div>

                </div>
            </div>
        </div>

    </div><!-- End .col-lg-12  -->

    <div class="col-lg-4"></div>
</div>

