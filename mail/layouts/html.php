<?php
use yii\helpers\Html;

/* @var $this \yii\web\View view component instance */
/* @var $message \yii\mail\MessageInterface the message being composed */
/* @var $content string main view render result */


?>
<?php $this->beginPage() ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?= \Yii::$app->charset ?>" />
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body>
    <?php $this->beginBody() ?>
    <div style="width:100%;min-width:700px;background-position:0 0;background-repeat:no-repeat;border:0 none;font-size:100%;font-style:inherit;font-weight:inherit;font:Arial,Helvetica,sans-serif;margin:0;padding:0;text-align:left;vertical-align:baseline;">
      <div style="background:url(<?= \yii\helpers\Url::base(true) . \Yii::$app->getAssetManager()->getAssetUrl(\skeeks\cms\assets\CmsAsset::register($this), 'mail/html_background.png'); ?>) repeat-x scroll 0 0 #BACB8C;color:#363636;padding:20px;">
        <div style="width:92%;margin:20px auto 20px;padding:10px;background:#fff;">
          <table style="width: 100%;">
            <tbody>
            <tr>
              <td colspan="2">
                  <?= $content; ?>
              </td>
            </tr>

            <tr>
                <td colspan="2">
                    <p style="background:url(<?= \yii\helpers\Url::base(true) . \Yii::$app->getAssetManager()->getAssetUrl(\skeeks\cms\assets\CmsAsset::register($this), 'mail/dashed.gif'); ?>) repeat-x scroll 0 0 transparent;font-size:11px;padding:17px 0 0;line-height:21px;margin:0 0 21px;"></p>
                </td>
            </tr>

            <tr>
                <td valign="center" style="width:50px;">
                  <a href="<?= \Yii::$app->cms->descriptor->homepage; ?>" target="_blank" style="width: 50px;">
                      <img src="<?= \Yii::$app->cms->logo(); ?>">
                  </a>
              </td>
                <td valign="top">
                  <p style="font-size:11px;padding:1px 0 0;line-height:21px;margin:0 0 1px;">
                      Если у вас возникли проблемы по работе с нашим сайтом — <a style="color:#66801C;" href="mailto:<?= \Yii::$app->cms->adminEmail; ?>">напишите нам</a>.<br>
                      If you have any problems with our website — <a style="color:#66801C;" href="mailto:<?= \Yii::$app->cms->adminEmail; ?>" >please do not hesitate to contact us</a>.
                  </p>
                </td>
            </tr>
          </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>



