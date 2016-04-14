<?php
/**
 * unauthorized
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 26.02.2015
 * @since 1.0.0
 */
use skeeks\cms\modules\admin\assets\AdminAsset;
use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\widgets\Breadcrumbs;
use skeeks\cms\helpers\UrlHelper;

/* @var $this \yii\web\View */
/* @var $content string */

\skeeks\cms\modules\admin\assets\AdminUnauthorizedAsset::register($this);
\Yii::$app->admin->registerAsset($this)->initJs();

$urlBg = \Yii::$app->assetManager->getAssetUrl(\skeeks\cms\modules\admin\assets\AdminAsset::register($this), 'images/bg/582738_www.Gde-Fon.com.jpg');
$blockerLoader = \Yii::$app->getAssetManager()->getAssetUrl(\Yii::$app->getAssetManager()->getBundle(\skeeks\cms\modules\admin\assets\AdminAsset::className()), 'images/loaders/circulare-blue-24_24.GIF');

$this->registerCss(<<<CSS
    body.sx-styled
    {
        background: url({$urlBg}) center fixed;
    }
CSS
);
$this->registerJs(<<<JS
    (function(sx, $, _)
    {
        sx.AppUnAuthorized = new sx.classes.AppUnAuthorized({
            'blockerLoader': '{$blockerLoader}'
        });
    })(sx, sx.$, sx._);
JS
);
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
<body>
<?php $this->beginBody() ?>

<div class="navbar" role="navigation">
    <div class="navbar-header sx-header-logo">
        <?= Html::a("<span><img src='" . \Yii::$app->cms->logo() . "' /> " . \Yii::$app->cms->descriptor->name . "</span>", \Yii::$app->cms->moduleAdmin()->createUrl(["admin/index/index"]), ["class" => "navbar-brand"]); ?>
    </div>

    <ul class="nav navbar-nav navbar-right visible-md visible-lg">
        <!--<li><span class="timer"><i class="icon-clock"></i> <span id="clock"></span></span></li>-->
        <li class="dropdown visible-md visible-lg"></li>

            <!--<a href="/">Перейти на сайт &rarr;</a>-->
        <li class="sx-left-border dropdown visible-md visible-lg visible-sm visible-xs">
            <a href="/" style="width: auto;" data-sx-widget="tooltip-b" data-original-title="<?=\Yii::t('app','To main page of site')?>"><i class="glyphicon glyphicon-globe"></i></a>
        </li>

    </ul>
</div>

<!-- begin canvas animation bg -->
      <div id="canvas-wrapper">
        <canvas id="demo-canvas"></canvas>
      </div>

<?= \skeeks\cms\modules\admin\widgets\Alert::widget(); ?>
<?= $content ?>

<div style="display: none;">
    <img src="<?= $urlBg; ?>" id="sx-auth-bg"/></div>
</div>

    <?php echo $this->render('_footer'); ?>
    <?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
