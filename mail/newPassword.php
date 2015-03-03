<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $user common\models\User */
?>

Привет, <?= Html::encode($user->username) ?>,
Ваш новый пароль: <?= $password?>