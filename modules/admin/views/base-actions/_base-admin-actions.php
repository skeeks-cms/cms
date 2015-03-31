<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 26.03.2015
 */
?>

<?php /*\skeeks\cms\modules\admin\widgets\Pjax::begin([
    'id' => 'sx-pjax-global',
    'formSelector' => 'body',
    'linkSelector' => '.sidebar-menu li a',
]); */?>

    <div class="panel panel-primary sx-panel sx-panel-content">
        <div class="panel-heading sx-no-icon">
            <h2>
                <?= \yii\widgets\Breadcrumbs::widget([
                    'homeLink' => ['label' => \Yii::t("yii", "Home"), 'url' => [
                        'admin/index',
                        'namespace' => 'admin'
                    ]],
                    'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                ]) ?>
            </h2>
            <div class="panel-actions">
            </div>
        </div><!-- End .panel-heading -->
        <div class="panel-body">
                <div class="panel-content-before">
                    <? if (!\skeeks\cms\helpers\UrlHelper::constructCurrent()->getSystem(\skeeks\cms\modules\admin\Module::SYSTEM_QUERY_NO_ACTIONS_MODEL)) : ?>
                        <?= $this->params['actions'] ?>
                    <? endif; ?>
                    <?/*= Alert::widget() */?>
                </div>
                <div class="panel-content sx-unblock-onWindowReady">
                    <!--<div class="sx-show-onWindowReady">-->
                        <?= \skeeks\cms\modules\admin\widgets\Alert::widget(); ?>
                        <? if ($viewF) : ?>
                           <?
                            try
                            {
                                echo \Yii::$app->view->render($viewF, $paramsFile, $contextFile);
                            } catch (\yii\base\InvalidParamException $e)
                            {
                                echo $e->getMessage();
                            }

                            ?>
                           <?/*= $this->render($viewFile, $paramsFile); */?>
                        <? else : ?>
                            <?= $content ?>
                        <? endif ; ?>
                    <!--</div>-->
                </div><!-- End .panel-body -->
        </div><!-- End .panel-body -->
    </div><!-- End .widget -->


<?php /*\skeeks\cms\modules\admin\widgets\Pjax::end(); */?>