<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $user common\models\User */
?>

<h1 style="color:#1D5800;font-size:32px;font-weight:normal;margin-bottom:13px;margin-top:20px;">Новый пароль на <?= \Yii::$app->name; ?></h1>

<p style="font:Arial,Helvetica,sans-serif;">
    Здравствуйте!<br><br>Для авторизации на сайте <?= Html::a(\Yii::$app->name, \yii\helpers\Url::home(true)) ?> используйте новый пароль.<br>
    <b><?= $password ?></b>
</p>
