<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $user common\models\User */

//$resetLink = Yii::$app->urlManager->createAbsoluteUrl(['cms/reset-password', 'token' => $user->password_reset_token]);
$resetLink = \skeeks\cms\helpers\UrlHelper::construct('admin/auth/reset-password', ['token' => $user->password_reset_token])->enableAbsolute()->enableAdmin();
?>

Привет, <?= Html::encode($user->username) ?>,
Перейдите по ссылке ниже, чтобы сбросить пароль:

<?= Html::a(Html::encode($resetLink), $resetLink) ?>
