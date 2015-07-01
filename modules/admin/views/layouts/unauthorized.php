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
use skeeks\cms\App;
use skeeks\cms\helpers\UrlHelper;

/* @var $this \yii\web\View */
/* @var $content string */

AdminAsset::register($this);
\Yii::$app->admin->registerAsset($this);

\skeeks\cms\modules\admin\assets\AdminCanvasBg::register($this);

$urlBg = \Yii::$app->assetManager->getAssetUrl(\skeeks\cms\modules\admin\assets\AdminAsset::register($this), 'images/bg/582738_www.Gde-Fon.com.jpg');
$blockerLoader = \Yii::$app->getAssetManager()->getAssetUrl(\skeeks\cms\modules\admin\assets\AdminAsset::register($this), 'images/loaders/circulare-blue-24_24.GIF');

$this->registerCss(<<<CSS

body.sx-styled
{
    background: url({$urlBg}) center fixed;
}

.navbar.op-05:hover, .sx-admin-footer.op-05:hover
{
    opacity: 1;
    transition-duration: 1s;
}

.navbar, .sx-admin-footer {
    display: none;
}
.navbar.op-05, .sx-admin-footer.op-05 {
    opacity: 0.5;
}

.sx-hidden
{
    display: none;
}

form.sx-form-admin
{
    border: none;
    padding: 0px;
}

.main {
    padding: 0px;
}


#canvas-wrapper {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  width: 100%;
  height: 100%;
}

CSS
);

$this->registerJs(<<<JS
    (function(sx, $, _)
    {
        sx.createNamespace('classes', sx);

        sx.classes.AppUnAuthorized = sx.classes.Component.extend({

            _init: function()
            {
                this.blocker    = new sx.classes.Blocker();
            },

            _onDomReady: function()
            {
                var self = this;
                this.blockerHtml = sx.block('html', {
                    message: "<div style='padding: 10px;'><h2><img src='{$blockerLoader}'/> Загрузка...</h2></div>",
                    css: {
                        "border-radius": "6px",
                        "border-width": "3px",
                        "border-color": "rgba(32, 168, 216, 0.25)",
                        "box-shadow": "0 11px 51px 9px rgba(0,0,0,.55)"
                    }
                });

                this.blockerLogin = new sx.classes.Blocker('.sx-panel', {
                    message: "<div style='padding: 10px;'><h2><img src='{$blockerLoader}'/> Загрузка...</h2></div>",
                    css: {
                        "border-radius": "6px",
                        "border-width": "1px",
                        "border-color": "rgba(32, 168, 216, 0.25)",
                        "box-shadow": "0 11px 51px 9px rgba(0,0,0,.55)"
                    }
                });

             // Init CanvasBG and pass target starting location
                CanvasBG.init({
                  Loc: {
                    x: window.innerWidth / 2.1,
                    y: window.innerHeight / 2.2
                  },
                });

            },

            _onWindowReady: function()
            {
                var self = this;
                $("body").addClass('sx-styled');

                this.blockerHtml.unblock();

                _.delay(function()
                {
                    $('.navbar, .sx-admin-footer').addClass('op-05').fadeIn();
                }, 1000);
            },

            loginnedSuccess: function(urlGo)
            {
                var self = this;

                _.delay(function()
                {
                    $(".navbar").slideUp(800);
                    $(".sx-admin-footer").slideUp(800);
                }, 300);

                _.delay(function()
                {
                    self.goActSuccessLogin();
                }, 300);

                _.delay(function()
                {
                    window.location.href = urlGo;
                }, 3000);
            }
        });

        new sx.classes.AppUnAuthorized({});
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
        <?= Html::a("<span><img src='" . \Yii::$app->cms->logo() . "' /> " . \Yii::$app->cms->moduleCms()->getDescriptor()->name . "</span>", \Yii::$app->cms->moduleAdmin()->createUrl(["admin/index/index"]), ["class" => "navbar-brand"]); ?>
    </div>

    <ul class="nav navbar-nav navbar-right visible-md visible-lg">
        <!--<li><span class="timer"><i class="icon-clock"></i> <span id="clock"></span></span></li>-->
        <li class="dropdown visible-md visible-lg"></li>

            <a href="/">Перейти на сайт &rarr;</a>
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



<footer class="sx-admin-footer">
    <div class="row">
        <div class="col-sm-5">
            <div class="sx-footer-copyright">
                <a href="http://cms.skeeks.com" target="_blank" data-sx-widget="tooltip" title="Перейти на сайт SkeekS CMS">
                    <?= \Yii::$app->cms->moduleCms()->getDescriptor()->getCopyright(); ?>
                </a>
                | <a href="http://skeeks.com" target="_blank" data-sx-widget="tooltip" title="Перейти на сайт разработчика системы">SkeekS.com</a>
            </div>
        </div><!--/.col-->
        <div class="col-sm-7 text-right">

        </div><!--/.col-->
    </div><!--/.row-->
</footer>


    <?php $this->endBody() ?>

</body>
</html>
<?php $this->endPage() ?>
