<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $user common\models\User */

//$resetLink = Yii::$app->urlManager->createAbsoluteUrl(['cms/reset-password', 'token' => $user->password_reset_token]);
$resetLink = \skeeks\cms\helpers\UrlHelper::construct('admin/auth/reset-password', ['token' => $user->password_reset_token])->enableAbsolute()->enableAdmin();
?>

<h1 style="color:#1D5800;font-size:32px;font-weight:normal;margin-bottom:13px;margin-top:20px;">
    Напоминание пароля на <?= \Yii::$app->name ?>
</h1>

<p style="font:Arial,Helvetica,sans-serif;">
    Здравствуйте!<br><br>Был получен запрос на смену пароля на сайте <?= Html::a(\Yii::$app->name, \yii\helpers\Url::home(true)) ?>.<br>
    <?= Html::a("Проследуйте по ссылке", $resetLink) ?> и мы вам пришлем новый пароль.<br>Если вы не запрашивали смену пароля, просто проигнорируйте это письмо.
</p>
