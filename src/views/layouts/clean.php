<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 26.06.2015
 */

/* @var $this \yii\web\View */

use yii\helpers\Html;

?>
<?php $this->beginPage() ?>
    <!DOCTYPE html>
    <html lang="<?= Yii::$app->language ?>">
    <head>
        <meta charset="<?= Yii::$app->charset ?>"/>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <?= Html::csrfMetaTags() ?>
        <title><?= Html::encode($this->title) ?></title>
        <link rel="icon" href="/favicon.ico?v=<?= @filemtime(\Yii::getAlias('@app/web/favicon.ico')); ?>"
              type="image/x-icon"/>
        <?php $this->head() ?>
    </head>
    <body>
    <?php $this->beginBody() ?>
    <?= $content; ?>
    <?php $this->endBody() ?>
    </body>
    </html>
<?php $this->endPage() ?>