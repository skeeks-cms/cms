<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $user common\models\User */
?>

<?= Html::beginTag('p'); ?>
Здравствуйте!
<?= Html::endTag('p'); ?>

<?= Html::beginTag('p'); ?>
Используйте новый пароль <b><?= $password ?></b> для авторизации на сайте <?= Html::a(\Yii::$app->cms->appName, \yii\helpers\Url::home(true)) ?>.
<?= Html::endTag('p'); ?>

<?= Html::beginTag('p'); ?>
При необходимости Вы сможете изменить его в личном Кабинете.
<?= Html::endTag('p'); ?>

