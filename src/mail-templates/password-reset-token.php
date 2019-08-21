<?php

use skeeks\cms\mail\helpers\Html;

/* @var $this yii\web\View */
/* @var $user common\models\User */
/* @var $resetLink */
if (!$resetLink) {
    $resetLink = \skeeks\cms\helpers\UrlHelper::construct('admin/auth/reset-password',
        ['token' => $user->password_reset_token])->enableAbsolute()->enableAdmin();
}
?>

<?/*= Html::beginTag('h1'); */?><!--
Напоминание пароля на <?/*= \Yii::$app->cms->appName */?>
--><?/*= Html::endTag('h1'); */?>

<?= Html::beginTag('p'); ?>
Здравствуйте!
<?= Html::endTag('p'); ?>

<?= Html::beginTag('p'); ?>
Мы получили запрос на восстановление пароля для <?= $user->email; ?> для сайта <?= Html::a(\Yii::$app->name, \yii\helpers\Url::home(true)); ?>.
<?= Html::endTag('p'); ?>

<?= Html::beginTag('p'); ?>
Если Вы хотите восстановить пароль, кликните по ссылке: <?= Html::a($resetLink, $resetLink) ?>
<?= Html::endTag('p'); ?>

<?= Html::beginTag('p'); ?>
Если Вы НЕ запрашивали восстановление пароля, просто проигнорируйте это письмо.
<?= Html::endTag('p'); ?>

