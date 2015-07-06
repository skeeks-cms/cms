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
                        <?= $message; ?>
                    </div>

                </div>
            </div>
        </div>

    </div><!-- End .col-lg-12  -->

    <div class="col-lg-4"></div>
</div>

