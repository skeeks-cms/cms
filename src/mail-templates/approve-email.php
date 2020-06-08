<?php

use skeeks\cms\mail\helpers\Html;

/* @var $this yii\web\View */
/* @var $resetLink */

?>
<?= Html::beginTag('p'); ?>
Здравствуйте!
<?= Html::endTag('p'); ?>

<?= Html::beginTag('p'); ?>
Вы успешно зарегистрированны.
<?= Html::endTag('p'); ?>


<?= Html::beginTag('p'); ?>
Для аткивации вашей учетной записи перейдите по ссылке: <?= $approveUrl; ?>
<?= Html::endTag('p'); ?>

