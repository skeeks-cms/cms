<?php
/**
 * index
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 08.11.2014
 * @since 1.0.0
 */
/* @var $this yii\web\View */

$url = \skeeks\cms\helpers\UrlHelper::construct('admin/clear/index')->enableAdmin()->toString();
$data = \yii\helpers\Json::encode([
    'backend' => $url
])
?>

<?= \yii\helpers\Html::a("Удалить временные файлы", $url, [
    'class'             => 'btn btn-primary',
    'onclick'           => 'new sx.classes.Clear(' . $data . '); return false;'
]); ?>
<hr />
<? foreach ($clearDirs as $dataDir) : ?>
    <?php
    /**
     * @var \skeeks\sx\Dir $dir
     */
        $dir = \yii\helpers\ArrayHelper::getValue($dataDir, 'dir');
    ?>
    <div class="row-fluid">
        <b><?= \yii\helpers\ArrayHelper::getValue($dataDir, 'label'); ?></b>: <?= $dir->getSize()->formatedShortSize(); ?><br />
        <?= $dir->getPath(); ?>
    </div>
    <hr />
<? endforeach; ?>

<?
    $this->registerJs(<<<JS
    (function(sx, $, _)
    {
        sx.createNamespace('classes.Clear', sx);

        sx.classes.Clear = sx.classes.Component.extend({

            _init: function()
            {
                var ajax = sx.ajax.preparePostQuery(this.get("backend"));

                new sx.classes.AjaxHandlerNotify(ajax);
                new sx.classes.AjaxHandlerNoLoader(ajax);
                new sx.classes.AjaxHandlerBlocker(ajax, {
                    'wrapper': '.sx-panel .panel-content'
                });

                ajax.onError(function(e, data)
                {
                    sx.notify.info("Подождите сейчас страница будет перезагружена");
                    _.delay(function()
                    {
                        window.location.reload();
                    }, 2000);
                })

                ajax.execute();
            },

            _onDomReady: function()
            {},

            _onWindowReady: function()
            {}
        });

    })(sx, sx.$, sx._);
JS
)
?>