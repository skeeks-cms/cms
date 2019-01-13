<?php

use skeeks\cms\mail\helpers\Html;

/* @var $this yii\web\View */
/* @var $user common\models\User */
/* @var $resetLink */
?>

<?/*= Html::beginTag('h1'); */?><!--
    Регистрация на сайте <?/*= \Yii::$app->cms->appName */?>
--><?/*= Html::endTag('h1'); */?>

<?= Html::beginTag('p'); ?>
    Здравствуйте!<br><br>Вы успешно зарегистрированны на сайте <?= Html::a(\Yii::$app->name,
    \yii\helpers\Url::home(true)) ?>.<br>
<?= Html::endTag('p'); ?>

<?= Html::beginTag('p'); ?>
    Для авторизации на сайте используйте следующие данные:
    <br>
    <br>
    <b>Email: </b><?= $user->email; ?><br>
    <b>Пароль: </b><?= $password; ?><br>
<br>
<?= Html::a("Войти в кабинет", \skeeks\cms\helpers\UrlHelper::construct('cms/auth/login')
    /*->setRef(
        \skeeks\cms\helpers\UrlHelper::construct('/cms/profile')->enableAbsolute()->toString()
    )*/
    ->enableAbsolute()
    ->toString()
) ?>
<?= Html::endTag('p'); ?>