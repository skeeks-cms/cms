<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 24.06.2015
 */
/* @var $this yii\web\View */
/* @var string $packagistCode */
/* @var $packageModel PackageModel */

use \skeeks\cms\components\marketplace\models\PackageModel;
$self = $this;

$attrDisabled = [];
if (!\Yii::$app->user->can('admin/ssh'))
{
    $attrDisabled = ['disabled' => 'disabled'];
}

?>

<div id="sx-search" style="margin-bottom: 10px;">
    <? $form = \skeeks\cms\modules\admin\widgets\ActiveForm::begin([
        'method' => 'get',
        'options' =>
        [
            'id'    => 'sx-package-search',
            'class' => 'form-inline'
        ]
    ]); ?>
        <?= \yii\helpers\Html::button('<i class="glyphicon glyphicon-retweet"></i> '.\Yii::t('app','Start update'),
        \yii\helpers\ArrayHelper::merge([
            'type'  => 'submit',
            'class' => 'btn btn-lg ' . ($attrDisabled ?: 'btn-primary'),
            'onclick' => new \yii\web\JsExpression(<<<JS
            sx.Installer.update(); return false;
JS
)

        ], $attrDisabled)

    ); ?>
    <hr />
    <p><b><a data-pjax="0" href="<?= \skeeks\cms\models\CmsExtension::getInstance('skeeks/cms')->adminUrl; ?>"><?=\Yii::t('app','{yii} Version',['yii' => 'SkeekS CMS'])?></a>: </b> <?= \Yii::$app->cms->descriptor->version; ?></p>
    <p><b><?=\Yii::t('app','{yii} Version',['yii' => 'Yii'])?>: </b> <?= Yii::getVersion(); ?></p>
    <? \skeeks\cms\modules\admin\widgets\ActiveForm::end(); ?>
</div>


<?= \skeeks\cms\widgets\installer\InstallerWidget::widget(); ?>
<?
$this->registerJs(<<<JS
(function(sx, $, _)
{
    sx.classes.InstallerRemover = sx.classes.Component.extend({

        _init: function()
        {},

        _onDomReady: function()
        {
            var self = this;
            self.SearchBlocker = null;
            sx.Installer.TaskManager.bind('start', function()
            {
                self.SearchBlocker = new sx.classes.Blocker('#sx-search');
                self.SearchBlocker.block();
            });

            sx.Installer.TaskManager.bind('stop', function()
            {
                self.SearchBlocker.unblock();
                _.delay(function()
                {
                    $('#sx-package-search').submit();
                }, 500);
            });
        },

        _onWindowReady: function()
        {}
    });

    new sx.classes.InstallerRemover();

})(sx, sx.$, sx._);
JS
)
?>

<?
    \yii\bootstrap\Alert::begin([
        'options' => [
          'class' => 'alert-info',
      ]
    ]);
?>
    <p><?=\Yii::t('app','Updating the platform usually lasts 1-5 minutes.')?></p>
    <p><?=\Yii::t('app','The updating process will change all your modifications to the core.')?></p>
    <p><?=\Yii::t('app','Will be created a backup of the database')?></p>
    <p><?=\Yii::t('app','Cleared cache')?></p>
    <p><?=\Yii::t('app','Deleted temporary files')?></p>
    <p><?=\Yii::t('app','Updated all included packages')?></p>

<? \yii\bootstrap\Alert::end(); ?>


