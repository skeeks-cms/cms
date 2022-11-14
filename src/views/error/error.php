<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $name string */
/* @var $message string */
/* @var $exception Exception */

$this->title = $name;
$this->registerCss(<<<CSS
.sx-error-section  {
    min-height: 50vh;
    display: flex;
    text-align: center;
}
.sx-error-section .sx-container {
    margin: auto;
}
.sx-buttons {
    margin-top: 20px;
}
CSS
);
?>
<div class="sx-error-section">

    <div class="container sx-container">
        <h1><?= \Yii::t('skeeks/cms', 'Ошибка'); ?> <?= isset($exception->statusCode) ? $exception->statusCode : $exception->getCode(); ?></h1>
        <?= nl2br(Html::encode($message)); ?>
        <div class="sx-buttons">
            <?php if(\Yii::$app->request->referrer) : ?>
                <a href="<?php echo \Yii::$app->request->referrer; ?>" class="btn btn-primary btn-xl"><?= \Yii::t('skeeks/cms', 'Назад'); ?></a>
            <?php endif; ?>
            <a href="<?php echo \yii\helpers\Url::home(); ?>" class="btn btn-primary btn-xl"><?= \Yii::t('skeeks/cms', 'Вернуться на главную'); ?></a>
        </div>
    </div>
</div>
