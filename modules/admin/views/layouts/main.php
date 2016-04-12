<?php
use skeeks\cms\modules\admin\assets\AdminAsset;
use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\widgets\Breadcrumbs;
use skeeks\cms\helpers\UrlHelper;

/* @var $this \yii\web\View */
/* @var $content string */

AdminAsset::register($this);
\Yii::$app->admin->registerAsset($this)->initJs();
\skeeks\cms\modules\admin\widgets\UserLastActivityWidget::widget();
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
    <head>
        <meta charset="<?= Yii::$app->charset ?>"/>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <?= Html::csrfMetaTags() ?>
        <title><?= Html::encode($this->title) ?></title>
        <link rel="icon" href="http://skeeks.com/favicon.ico"  type="image/x-icon" />
        <?php $this->head() ?>
    </head>
    <body class="<?= \Yii::$app->user->isGuest ? "sidebar-hidden" : ""?> <?= \Yii::$app->admin->isEmptyLayout() ? "empty" : ""?>">
<?php $this->beginBody() ?>
    <?= $this->render('_header'); ?>
    <? if (!\Yii::$app->user->isGuest): ?>
        <?= $this->render('_admin-menu'); ?>
    <? endif; ?>
        <div class="main">

            <?= $this->render('_main-head'); ?>

            <div class="col-lg-12 sx-main-body">
                <? $openClose = \Yii::t('app', 'Expand/Collapse')?>
                <? \skeeks\cms\modules\admin\widgets\AdminPanelWidget::begin([
                    'name'      => property_exists(\Yii::$app->controller, 'name') ? \Yii::$app->controller->name : "",
                    'actions'   => <<<HTML
                        <a href="#" class="sx-btn-trigger-full">
                            <i class="glyphicon glyphicon-fullscreen" data-sx-widget="tooltip-b" data-original-title="{$openClose}" style="color: white;"></i>
                        </a>
HTML
,

                    'options' =>
                    [
                        'class' => 'sx-main-content-widget sx-panel-content',
                    ],
                ]); ?>
                   <div class="panel-content-before">
                        <? if (!UrlHelper::constructCurrent()->getSystem(\skeeks\cms\modules\admin\Module::SYSTEM_QUERY_NO_ACTIONS_MODEL)) : ?>
                            <?= \yii\helpers\ArrayHelper::getValue($this->params, 'actions'); ?>
                        <? endif; ?>
                    </div>
                    <div class="panel-content">
                        <?= \skeeks\cms\modules\admin\widgets\Alert::widget(); ?>
                        <?= $content ?>
                    </div>
                <? \skeeks\cms\modules\admin\widgets\AdminPanelWidget::end(); ?>
            </div>

        </div>
        <?php echo $this->render('_footer'); ?>
        <?php $this->endBody() ?>
    </body>
</html>
<?php $this->endPage() ?>
