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

        sx.classes.DbRestore = sx.classes.Component.extend({

            execute: function(name)
            {
                var ajaxQuery = sx.ajax.preparePostQuery(this.get('backendDbRestore'), {
                    'name' : name
                });

                new sx.classes.AjaxHandlerStandartRespose(ajaxQuery);
                var ajaxHandler = new sx.classes.AjaxHandlerBlocker(ajaxQuery, {
                    'wrapper': '.sx-panel'
                });

                new sx.classes.AjaxHandlerNoLoader(ajaxQuery);

                ajaxQuery.onComplete(function()
                {
                    window.location.reload();
                });

                ajaxQuery.execute();
            }
        });

        sx.DbRestore = new sx.classes.DbRestore({$jsOptions});

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
                            База данных пустая!
                        </div>

                        <div class="alert alert-info" role="alert">
                            Путь к файлу настроек: <?= \Yii::getAlias('@common/config/db.php'); ?><br />
                            Dsn: <?= \Yii::$app->db->dsn; ?>
                        </div>


                        <div style="text-align: center;">
                            <button class="btn btn-success btn-lg" onclick="sx.DbRestore.execute(); return false;">
                                <i class="glyphicon glyphicon-play"></i>
                                Запустить новую установку
                            </button>
                            <? if ($files = \skeeks\cms\helpers\FileHelper::findFiles(\Yii::$app->dbDump->backupDir)) : ?>
                                <hr />
                                <h2>Установить из бэкап файла</h2>
                                <? foreach ($files as $file) : ?>
                                    <? $fileObj = new \skeeks\sx\File($file); ?>
                                    <br />
                                    <button class="btn btn-success" onclick="sx.DbRestore.execute('<?= $fileObj->getBaseName(); ?>'); return false;">
                                        <i class="glyphicon glyphicon-play"></i>
                                        <?= $fileObj->getBaseName(); ?> (<?= $fileObj->size()->formatedShortSize(); ?>)
                                    </button>
                                <? endforeach; ?>
                            <? endif; ?>
                        </div>

                    </div>

                </div>
            </div>
        </div>

    </div><!-- End .col-lg-12  -->

    <div class="col-lg-4"></div>
</div>

