<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 29.06.2015
 */
/* @var $this yii\web\View */
/* @var $widget \skeeks\cms\widgets\installer\InstallerWidget */

?>
<div class="sx-box sx-p-10 sx-mb-10" style="display: none;" id="<?= $widget->id; ?>">
    <div class="sx-toggle-ssh sx-p-10" style="display: none;">
        <a href="#" class="btn btn-default btn-ssh-toggle"><?=\Yii::t('app','Show{s}Hide details',['s' => '/'])?></a>
    </div>

    <?=
        \yii\bootstrap\Alert::widget([
            'options' => [
              'class' => 'alert-danger sx-notify-install',
              'style' => 'display: none;',
            ],
          'body' => \yii\helpers\Html::tag("div", \Yii::t('app','The process of installing a new package, please do not close this page. This can lead to sad consequences of working your site.')),
        ]);
    ?>
    <div class="sx-progress-tasks" id="sx-progress-tasks" style="display: none;">
        <span style="vertical-align:middle;"><h3><?=\Yii::t('app','The installation process of extension (Progress')?> <span class="sx-executing-ptc">0</span>%)</h3></span>
        <span style="vertical-align:middle;"><?=\Yii::t('app','Stage')?>: <span class="sx-executing-task-name"></span></span>
        <div>
            <div class="progress progress-striped active">
                <div class="progress-bar progress-bar-success"></div>
            </div>
        </div>
        <hr />
    </div>
    <div id="sx-tmp-result"></div>
    <div id="sx-ssh-console-wrapper" style="display: none;">
        <?=
            \skeeks\cms\widgets\ssh\SshConsoleWidget::widget([
                'enabledTabs' => \skeeks\cms\components\Cms::BOOL_N,
                'consoleHeight' => '400px;'
            ]);
        ?>
    </div>

</div>


<?
$this->registerJs(<<<JS
    (function(sx, $, _)
    {
        sx.Installer = new sx.classes.Installer({$widget->getClientOptionsJson()});
    })(sx, sx.$, sx._);
JS
);
?>
