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
Здравствуйте!<br><br>Для вашего email мы получили запрос на смену пароля на сайте <?= Html::a(\Yii::$app->name,
    \yii\helpers\Url::home(true)) ?>.<br>
<?= Html::a("Проследуйте по ссылке", $resetLink) ?> и мы вам пришлем новый пароль.
<br><br><small>Если вы не запрашивали смену пароля, просто проигнорируйте это письмо.</small>
<?= Html::endTag('p'); ?>
