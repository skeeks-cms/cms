<?php

use skeeks\cms\mail\helpers\Html;

/* @var $this yii\web\View */
/* @var $user common\models\User */
/* @var $resetLink */
?>

<?= Html::beginTag('h1'); ?>
Тестирование отправки писем с сайта <?= \Yii::$app->cms->appName ?>
<?= Html::endTag('h1'); ?>

<?= Html::beginTag('p'); ?>
Здравствуйте!<br><br>Отправка произведена с сайта <?= Html::a(\Yii::$app->name, \yii\helpers\Url::home(true)) ?>.<br>
Это письмо можно просто удалить.
<?= Html::endTag('p'); ?>
