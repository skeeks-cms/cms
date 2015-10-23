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

$attrDisabled = '';
if (!\Yii::$app->user->can('admin/ssh'))
{
    $attrDisabled = 'disabled="disabled"';
}

?>

<div id="sx-search">

<? $form = \skeeks\cms\modules\admin\widgets\ActiveForm::begin([
    'method' => 'get',
    'options' =>
    [
        'id'    => 'sx-package-search',
        'class' => 'form-inline'
    ]
]); ?>

      <div class="form-group">
        <div class="input-group">
          <div class="input-group-addon"><?=\Yii::t('app','Solution code')?></div>
          <?= \yii\helpers\Html::textInput('packagistCode', $packagistCode, [
                'class' => 'form-control',
                'placeholder' => 'skeeks/cms'
            ]); ?>
        </div>
      </div>

        <?= \yii\helpers\Html::button(\Yii::t('app','Find'), [
            'type'  => 'submit',
            'class' => 'btn btn-primary'
        ]); ?>

    <? if ($packageModel) : ?>
        <?= \skeeks\cms\modules\admin\widgets\GridView::widget([
            'dataProvider' => (new \yii\data\ArrayDataProvider([
                'allModels' => [$packageModel],
                'pagination' => [
                    'defaultPageSize' => 1
                ]
            ])),
            'layout' => "{items}",
            'columns' =>
            [
                [
                    'class' => \yii\grid\DataColumn::className(),
                    'value' => function(PackageModel $model) use ($self)
                    {
                        return $self->render('_package-column', [
                            'model' => $model
                        ]);
                    },
                    'format' => 'raw'
                ],

                [
                    'class' => \yii\grid\DataColumn::className(),
                    'value' => function(PackageModel $model)
                    {
                        if ($model->isInstalled())
                        {
                            return $model->createCmsExtension()->version;
                        } else
                        {
                            return ' — '
;
                        }
                    },
                    'label' => \Yii::t('app','Version'),
                    'format' => 'raw'
                ],

                [
                    'class' => \yii\grid\DataColumn::className(),
                    'value' => function(PackageModel $model) use ($attrDisabled)
                    {
                        if ($model->isInstalled())
                        {
                            $extension = $model->createCmsExtension();
                            $code = $model->packagistCode;


                            if ($extension->canDelete())
                            {
                                if (!$attrDisabled)
                                {
                                    $class = ' btn-danger';
                                }

                                $sd = \Yii::t('app','Start deleting');
                                return <<<HTML
                                <a data-pjax="0"  class="btn btn-default {$class}" target="" title="" onclick="sx.Installer.remove('{$code}'); return false;" {$attrDisabled}>
                                    <i class="glyphicon glyphicon-remove"></i> {$sd}
                                </a>
HTML;
;
                            }
                        } else
                        {
                            if (!$attrDisabled)
                            {
                                $class = ' btn-success';
                            }

                            $code = $model->packagistCode;
                            $si = \Yii::t('app','Start installing');
                            $tit = \Yii::t('app','This will start the installation process, the latest stable version.');
                            return <<<HTML
                                <a data-pjax="0"  class="btn btn-default btn-success" target="" title="{$tit}" onclick="sx.Installer.install('{$code}:*'); return false;" {$attrDisabled}>
                                    <i class="glyphicon glyphicon-download-alt"></i> {$si}
                                </a>
HTML;
;
                        }
                    },
                    'label' => \Yii::t('app','Actions'),
                    'format' => 'raw'
                ],

            ]
        ])?>
    <? endif; ?>
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
    <p><?=\Yii::t('app','Install{s}Delete packages usually lasts 1-5 minutes.',['s' => '/'])?></p>
    <p><?=\Yii::t('app','The installation process will change all your modifications to the core.')?></p>
    <p><?=\Yii::t('app','Will be created a backup of the database')?></p>
    <p><?=\Yii::t('app','Cleared cache')?></p>
    <p><?=\Yii::t('app','Deleted temporary files')?></p>

<? \yii\bootstrap\Alert::end(); ?>


